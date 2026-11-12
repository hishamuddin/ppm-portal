<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $r)
    {
        $q = Media::query();
        if (! $r->user()->canPublish()) {
            $q->where('user_id', $r->user()->id);
        }

        return view('admin.media', ['items' => $q->latest()->paginate(12)]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240', 'alt' => 'required|string|max:255']);
        $file = $r->file('file');
        $path = $file->store('media', 'local');
        try {
            $media = Media::create(['user_id' => $r->user()->id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 240), 'path' => $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'alt' => $data['alt'], 'is_public' => false]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        AuditLog::record('media.uploaded', (string) $media->id);

        return back()->with('success', 'File uploaded privately. An editor can publish it.');
    }

    public function update(Request $r, Media $media)
    {
        abort_unless($r->user()->canPublish(), 403);
        $data = $r->validate(['alt' => 'required|string|max:255', 'is_public' => 'required|boolean']);
        $media->update($data);
        AuditLog::record('media.updated', (string) $media->id);

        return back()->with('success', 'Media visibility updated.');
    }

    public function show(Request $r, Media $media)
    {
        $user = $r->user();
        abort_unless($media->is_public || ($user?->active && ($user->canPublish() || $media->user_id === $user->id)), 404);
        abort_unless(Storage::disk('local')->exists($media->path), 404);
        $response = Storage::disk('local')->response($media->path, $media->name, ['Content-Type' => $media->mime, 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"], str_starts_with($media->mime, 'image/') ? 'inline' : 'attachment');

        return $response;
    }

    public function destroy(Request $r, Media $media)
    {
        abort_unless($r->user()->canPublish() || ($media->user_id === $r->user()->id && ! $media->is_public), 403);
        Storage::disk('local')->delete($media->path);
        $media->delete();
        AuditLog::record('media.deleted', (string) $media->id);

        return back()->with('success', 'File deleted.');
    }
}
