<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\Revision;
use App\Services\ContentHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function index(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:150', 'status' => 'nullable|in:draft,review,published', 'type' => 'nullable|in:post,page,speech,statement,gallery']);
        $q = Content::with('author');
        if (! $r->user()->canPublish()) {
            $q->where('user_id', $r->user()->id);
        }
        if ($r->filled('q')) {
            $q->where('title', 'like', '%'.$r->string('q').'%');
        }
        foreach (['status', 'type'] as $field) {
            if ($r->filled($field)) {
                $q->where($field, $r->input($field));
            }
        }

        return view('admin.contents.index', ['items' => $q->latest('updated_at')->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return $this->form(new Content(['type' => 'post', 'locale' => 'ms', 'status' => 'draft', 'version' => 1]));
    }

    public function edit(Content $content)
    {
        Gate::authorize('update', $content);

        return $this->form($content);
    }

    private function form(Content $content)
    {
        return view('admin.contents.form', ['content' => $content, 'categories' => Category::orderBy('name')->get(), 'media' => Media::where('is_public', true)->where('mime', 'like', 'image/%')->latest()->get()]);
    }

    private function data(Request $r, ?Content $content = null): array
    {
        $data = $r->validate([
            'title' => 'required|string|max:255', 'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('contents')->where('locale', $r->input('locale'))->ignore($content?->id)],
            'locale' => 'required|in:ms,en', 'type' => 'required|in:page,post,speech,statement,gallery', 'excerpt' => 'nullable|string|max:600', 'body' => 'required|string|max:200000',
            'category_id' => 'nullable|exists:categories,id', 'media_id' => ['nullable', Rule::exists('media', 'id')->where('is_public', true)->where(fn ($q) => $q->where('mime', 'like', 'image/%'))],
            'gallery_ids' => 'nullable|array|max:30', 'gallery_ids.*' => ['integer', 'distinct', Rule::exists('media', 'id')->where('is_public', true)->where(fn ($q) => $q->where('mime', 'like', 'image/%'))],
            'tags' => 'nullable|string|max:255', 'status' => 'required|in:draft,review,published', 'published_at' => 'nullable|date', 'seo_title' => 'nullable|string|max:70', 'seo_description' => 'nullable|string|max:160', 'version' => 'required|integer|min:1',
        ]);
        abort_if($data['status'] === 'published' && ! $r->user()->canPublish(), 403);
        $data['gallery_ids'] = $data['gallery_ids'] ?? [];
        $data['body'] = ContentHtml::clean($data['body']);
        if (trim(strip_tags($data['body'])) === '') {
            throw ValidationException::withMessages(['body' => 'Enter readable content.']);
        }
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    public function store(Request $r)
    {
        $data = $this->data($r);
        $content = DB::transaction(function () use ($data, $r) {
            $c = Content::create([...$data, 'user_id' => $r->user()->id, 'version' => 1]);
            $this->revision($c);
            AuditLog::record('content.created', (string) $c->id);

            return $c;
        });

        return redirect()->route('admin.contents.edit', $content)->with('success', 'Content created.');
    }

    public function update(Request $r, Content $content)
    {
        Gate::authorize('update', $content);
        $data = $this->data($r, $content);
        DB::transaction(function () use ($content, $data) {
            $locked = Content::lockForUpdate()->findOrFail($content->id);
            Gate::authorize('update', $locked);
            abort_if($locked->version != (int) $data['version'], 409, 'This content changed. Reload before saving.');
            $locked->update([...$data, 'version' => $locked->version + 1]);
            $this->revision($locked);
            AuditLog::record('content.updated', (string) $locked->id);
        });

        return back()->with('success', 'Changes saved.');
    }

    private function revision(Content $c): void
    {
        $c->revisions()->create(['user_id' => auth()->id(), 'snapshot' => $c->only(['title', 'slug', 'locale', 'type', 'excerpt', 'body', 'tags', 'gallery_ids', 'category_id', 'media_id', 'seo_title', 'seo_description'])]);
    }

    public function preview(Content $content)
    {
        Gate::authorize('update', $content);

        return response()->view('portal.show', ['content' => $content, 'locale' => $content->locale, 'preview' => true])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function revisions(Content $content)
    {
        Gate::authorize('update', $content);

        return view('admin.contents.revisions', ['content' => $content, 'revisions' => $content->revisions()->paginate(20)]);
    }

    public function restore(Request $r, Content $content, Revision $revision)
    {
        Gate::authorize('update', $content);
        abort_unless($revision->content_id === $content->id, 404);
        $r->validate(['version' => 'required|integer']);
        DB::transaction(function () use ($content, $revision, $r) {
            $c = Content::lockForUpdate()->findOrFail($content->id);
            Gate::authorize('update', $c);
            abort_if($c->version != $r->integer('version'), 409);
            $snapshot = $revision->snapshot;
            unset($snapshot['slug'],$snapshot['locale']);
            $snapshot['body'] = ContentHtml::clean($snapshot['body']);
            if (! Category::whereKey($snapshot['category_id'])->exists()) {
                $snapshot['category_id'] = null;
            }
            if (! Media::whereKey($snapshot['media_id'])->exists()) {
                $snapshot['media_id'] = null;
            }
            $c->update([...$snapshot, 'status' => 'draft', 'published_at' => null, 'version' => $c->version + 1]);
            $this->revision($c);
            AuditLog::record('content.restored', (string) $c->id);
        });

        return redirect()->route('admin.contents.edit', $content)->with('success', 'Revision restored as a draft. URL and language preserved.');
    }

    public function destroy(Content $content)
    {
        DB::transaction(function () use ($content) {
            $locked = Content::lockForUpdate()->findOrFail($content->id);
            Gate::authorize('delete', $locked);
            $locked->delete();
            AuditLog::record('content.trashed', (string) $locked->id);
        });

        return redirect()->route('admin.contents.index')->with('success', 'Content moved to trash.');
    }
}
