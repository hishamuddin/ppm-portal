<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Content;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function dashboard(Request $r)
    {
        $q = Content::query();
        if (! $r->user()->canPublish()) {
            $q->where('user_id', $r->user()->id);
        }
        $counts = [];
        foreach (['draft', 'review', 'published'] as $status) {
            $counts[$status] = (clone $q)->where('status', $status)->count();
        }

        return view('admin.dashboard', ['counts' => $counts, 'recent' => (clone $q)->latest('updated_at')->limit(7)->get()]);
    }

    private function admin(Request $r): void
    {
        abort_unless($r->user()->role === 'admin', 403);
    }

    public function categories(Request $r)
    {
        abort_unless($r->user()->canPublish(), 403);

        return view('admin.categories', ['items' => Category::orderBy('name')->get()]);
    }

    public function saveCategory(Request $r)
    {
        abort_unless($r->user()->canPublish(), 403);
        Category::create($r->validate(['name' => 'required|string|max:100', 'slug' => 'required|alpha_dash:ascii|max:100|unique:categories,slug']));

        return back()->with('success', 'Category added.');
    }

    public function deleteCategory(Request $r, Category $category)
    {
        abort_unless($r->user()->canPublish(), 403);
        $category->delete();

        return back()->with('success', 'Category removed; content retained.');
    }

    public function settings(Request $r)
    {
        $this->admin($r);

        return view('admin.settings', ['settings' => Setting::read('portal', [])]);
    }

    public function saveSettings(Request $r)
    {
        $this->admin($r);
        $data = $r->validate(['title_ms' => 'required|string|max:100', 'title_en' => 'required|string|max:100', 'hero_ms' => 'required|string|max:180', 'hero_en' => 'required|string|max:180', 'description_ms' => 'required|string|max:400', 'description_en' => 'required|string|max:400', 'email' => 'required|email|max:150', 'phone' => 'required|string|max:40', 'address' => 'required|string|max:400', 'logo_media_id' => ['nullable', Rule::exists('media', 'id')->where('is_public', true)->where(fn ($q) => $q->where('mime', 'like', 'image/%'))]]);
        Setting::updateOrCreate(['key' => 'portal'], ['value' => $data]);
        AuditLog::record('settings.updated', 'portal');

        return back()->with('success', 'Portal settings saved.');
    }

    public function menus(Request $r)
    {
        $this->admin($r);

        return view('admin.menus', ['menus' => Setting::read('menus', ['ms' => [], 'en' => []])]);
    }

    public function saveMenus(Request $r)
    {
        $this->admin($r);
        $r->validate(['locale' => 'required|in:ms,en', 'items' => 'nullable|array|max:12', 'items.*.label' => 'nullable|string|max:60', 'items.*.url' => ['nullable', 'string', 'max:400', function ($a, $value, $fail) {
            if (! preg_match('~^/(?!/)[a-zA-Z0-9/._?=&%#-]*$~D', $value) && ! (filter_var($value, FILTER_VALIDATE_URL) && parse_url($value, PHP_URL_SCHEME) === 'https' && ! parse_url($value, PHP_URL_USER))) {
                $fail('Use a local path or an https URL.');
            }
        }]]);
        $items = [];
        foreach ($r->input('items', []) as $item) {
            if (! empty($item['label']) && ! empty($item['url'])) {
                $items[] = ['label' => $item['label'], 'url' => $item['url']];
            }
        }
        $menus = Setting::read('menus', ['ms' => [], 'en' => []]);
        $menus[$r->input('locale')] = $items;
        Setting::updateOrCreate(['key' => 'menus'], ['value' => $menus]);
        AuditLog::record('menus.updated', $r->input('locale'));

        return back()->with('success', 'Navigation saved.');
    }

    public function users(Request $r)
    {
        $this->admin($r);

        return view('admin.users', ['items' => User::orderBy('name')->paginate(20)]);
    }

    public function saveUser(Request $r)
    {
        $this->admin($r);
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:254|unique:users,email', 'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()], 'role' => 'required|in:admin,editor,author']);
        $u = new User;
        $u->name = $data['name'];
        $u->email = mb_strtolower($data['email']);
        $u->password = $data['password'];
        $u->role = $data['role'];
        $u->active = true;
        $u->save();
        AuditLog::record('user.created', (string) $u->id);

        return back()->with('success', 'Staff account created.');
    }

    public function updateUser(Request $r, User $user)
    {
        $this->admin($r);
        abort_if($r->user()->id === $user->id, 422, 'Use another administrator to change your role or account status.');
        $data = $r->validate(['role' => 'required|in:admin,editor,author', 'active' => 'required|boolean']);
        DB::transaction(function () use ($user, $data) {
            User::where('role', 'admin')->lockForUpdate()->get();
            abort_if($user->role === 'admin' && (! $data['active'] || $data['role'] !== 'admin') && User::where('role', 'admin')->where('active', true)->where('id', '!=', $user->id)->count() === 0, 422, 'Keep at least one active administrator.');
            $user->forceFill($data)->save();
        });
        AuditLog::record('user.updated', (string) $user->id);

        return back()->with('success', 'Staff account updated.');
    }

    public function profile()
    {
        return view('admin.profile');
    }

    public function password(Request $r)
    {
        $data = $r->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        $r->user()->update(['password' => $data['password']]);
        $r->session()->regenerate();

        return back()->with('success', 'Password changed.');
    }

    public function audit(Request $r)
    {
        $this->admin($r);

        return view('admin.audit', ['items' => AuditLog::latest('id')->paginate(30)]);
    }

    public function trash(Request $r)
    {
        abort_unless($r->user()->canPublish(), 403);

        return view('admin.trash', ['items' => Content::onlyTrashed()->latest('deleted_at')->paginate(20)]);
    }

    public function untrash(Request $r, int $id)
    {
        abort_unless($r->user()->canPublish(), 403);
        $c = Content::onlyTrashed()->findOrFail($id);
        $c->status = 'draft';
        $c->published_at = null;
        $c->version++;
        $c->restore();
        AuditLog::record('content.untrashed', (string) $id);

        return back()->with('success', 'Content recovered as a draft.');
    }
}
