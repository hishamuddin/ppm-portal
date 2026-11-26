<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function staff(string $role = 'admin'): User
    {
        $u = User::factory()->create();
        $u->role = $role;
        $u->save();

        return $u;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['title' => 'A public service', 'slug' => 'a-public-service', 'locale' => 'ms', 'type' => 'post', 'body' => '<p>Useful information</p>', 'status' => 'draft', 'version' => 1], $overrides);
    }

    private function content(User $user, array $overrides = []): Content
    {
        return Content::create([...$this->payload($overrides), 'user_id' => $user->id]);
    }

    public function test_public_home_and_locale_validation(): void
    {
        $this->get('/')->assertRedirect('/ms');
        $this->get('/ms')->assertOk()->assertSee('Maklumat');
        $this->get('/en')->assertOk()->assertSee('Clear information');
        $this->get('/fr')->assertNotFound();
    }

    public function test_admin_requires_authentication_and_active_staff(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $u = $this->staff();
        $u->active = false;
        $u->save();
        $this->actingAs($u)->get('/admin')->assertForbidden();
    }

    public function test_all_admin_screens_render(): void
    {
        $this->actingAs($this->staff());
        foreach (['/admin', '/admin/contents', '/admin/contents/create', '/admin/media', '/admin/categories', '/admin/settings', '/admin/menus', '/admin/users', '/admin/profile', '/admin/audit', '/admin/trash'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_drafts_review_future_and_trashed_content_are_not_public(): void
    {
        $u = $this->staff();
        foreach (['draft', 'review', 'future', 'trash'] as $i => $state) {
            $c = $this->content($u, ['slug' => $state, 'title' => 'Hidden '.$state, 'status' => in_array($state, ['draft', 'review']) ? $state : 'published', 'published_at' => $state === 'future' ? now()->addHour() : now()->subHour()]);
            if ($state === 'trash') {
                $c->delete();
            } $this->get('/ms/content/'.$state)->assertNotFound();
        }
        $this->get('/ms/search?q=Hidden')->assertOk()->assertDontSee('Hidden draft')->assertDontSee('Hidden future')->assertDontSee('Hidden review')->assertDontSee('Hidden trash');
    }

    public function test_due_content_is_public_in_correct_locale(): void
    {
        $u = $this->staff();
        $this->content($u, ['status' => 'published', 'published_at' => now()->subMinute()]);
        $this->get('/ms/content/a-public-service')->assertOk()->assertSee('Useful information');
        $this->get('/en/content/a-public-service')->assertNotFound();
    }

    public function test_author_can_create_and_submit_own_draft_but_cannot_publish_or_edit_others(): void
    {
        $author = $this->staff('author');
        $other = $this->staff('author');
        $c = $this->content($other);
        $this->actingAs($author)->get('/admin/contents/'.$c->id.'/edit')->assertForbidden();
        $this->post('/admin/contents', $this->payload(['slug' => 'mine', 'status' => 'review']))->assertRedirect();
        $this->assertDatabaseHas('contents', ['slug' => 'mine', 'user_id' => $author->id, 'status' => 'review']);
        $this->post('/admin/contents', $this->payload(['slug' => 'forbidden', 'status' => 'published']))->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
    }

    public function test_author_cannot_modify_own_published_content(): void
    {
        $u = $this->staff('author');
        $c = $this->content($u, ['status' => 'published', 'published_at' => now()]);
        $this->actingAs($u)->put('/admin/contents/'.$c->id, $this->payload())->assertForbidden();
        $this->delete('/admin/contents/'.$c->id)->assertForbidden();
    }

    public function test_editor_publishes_sanitized_content_with_revision(): void
    {
        $this->actingAs($this->staff('editor'))->post('/admin/contents', $this->payload(['status' => 'published', 'body' => '<p>Hello <strong>world</strong></p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><img src=x onerror=alert(1)>']))->assertRedirect();
        $c = Content::firstOrFail();
        $this->assertNotNull($c->published_at);
        $this->assertStringContainsString('<strong>world</strong>', $c->body);
        $this->assertStringNotContainsString('<script', $c->body);
        $this->assertStringNotContainsString('javascript:', $c->body);
        $this->assertStringNotContainsString('onerror', $c->body);
        $this->assertCount(1, $c->revisions);
    }

    public function test_conflicting_edits_return_409_and_preserve_saved_content(): void
    {
        $u = $this->staff();
        $c = $this->content($u);
        $this->actingAs($u)->put('/admin/contents/'.$c->id, $this->payload(['title' => 'Saved first']))->assertRedirect();
        $this->put('/admin/contents/'.$c->id, $this->payload(['title' => 'Stale overwrite']))->assertStatus(409);
        $this->assertSame('Saved first', $c->fresh()->title);
    }

    public function test_revision_restore_creates_a_draft_and_rejects_unrelated_revision(): void
    {
        $u = $this->staff();
        $this->actingAs($u)->post('/admin/contents', $this->payload())->assertRedirect();
        $c = Content::first();
        $revision = $c->revisions()->first();
        $this->put('/admin/contents/'.$c->id, $this->payload(['status' => 'published', 'title' => 'Changed']))->assertRedirect();
        $this->post('/admin/contents/'.$c->id.'/revisions/'.$revision->id.'/restore', ['version' => 2])->assertRedirect();
        $this->assertDatabaseHas('contents', ['id' => $c->id, 'status' => 'draft', 'title' => 'A public service', 'version' => 3]);
        $another = $this->content($u, ['slug' => 'another']);
        $this->post('/admin/contents/'.$another->id.'/revisions/'.$revision->id.'/restore', ['version' => 1])->assertNotFound();
    }

    public function test_private_media_is_not_leaked_and_editor_can_publish_it(): void
    {
        Storage::fake('local');
        $u = $this->staff('author');
        $this->actingAs($u)->post('/admin/media', ['file' => UploadedFile::fake()->image('photo.png'), 'alt' => 'A building'])->assertRedirect();
        $m = Media::first();
        $this->assertFalse($m->is_public);
        $this->put('/admin/media/'.$m->id, ['alt' => 'Public', 'is_public' => 1])->assertForbidden();
        auth()->logout();
        $this->get('/media/'.$m->id)->assertNotFound();
        $this->actingAs($this->staff('editor'))->put('/admin/media/'.$m->id, ['alt' => 'Public', 'is_public' => 1])->assertRedirect();
        auth()->logout();
        $this->get('/media/'.$m->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_executable_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs($this->staff())->post('/admin/media', ['file' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'), 'alt' => 'File'])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_unsafe_menu_urls_are_rejected(): void
    {
        $this->actingAs($this->staff());
        foreach (['javascript:alert(1)', '//evil.test', '/\\evil.test', 'data:text/html,test'] as $url) {
            $this->put('/admin/menus', ['locale' => 'ms', 'items' => [['label' => 'Link', 'url' => $url]]])->assertSessionHasErrors('items.0.url');
        } $this->assertDatabaseCount('settings', 0);
    }

    public function test_admin_cannot_disable_self_and_author_cannot_change_roles(): void
    {
        $u = $this->staff();
        $this->actingAs($u)->put('/admin/users/'.$u->id, ['role' => 'author', 'active' => 0])->assertStatus(422);
        $this->actingAs($this->staff('author'))->put('/admin/users/'.$u->id, ['role' => 'author', 'active' => 0])->assertForbidden();
    }

    public function test_deleted_content_can_only_be_recovered_by_editor_as_draft(): void
    {
        $u = $this->staff();
        $c = $this->content($u, ['status' => 'published', 'published_at' => now()]);
        $c->delete();
        $this->actingAs($this->staff('author'))->post('/admin/trash/'.$c->id)->assertForbidden();
        $this->actingAs($u)->post('/admin/trash/'.$c->id)->assertRedirect();
        $this->assertDatabaseHas('contents', ['id' => $c->id, 'deleted_at' => null, 'status' => 'draft', 'published_at' => null]);
    }

    public function test_disabled_user_cannot_login_and_throttle_is_enforced(): void
    {
        $u = $this->staff();
        $u->active = false;
        $u->save();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        } $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_successful_login_and_logout(): void
    {
        $u = $this->staff();
        $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_slug_is_unique_per_locale_including_trash(): void
    {
        $u = $this->staff();
        $c = $this->content($u);
        $c->delete();
        $this->actingAs($u)->post('/admin/contents', $this->payload())->assertSessionHasErrors('slug');
        $this->post('/admin/contents', $this->payload(['locale' => 'en']))->assertRedirect();
    }

    public function test_gallery_rejects_private_images_and_hides_revoked_public_images(): void
    {
        $u = $this->staff();
        $m = Media::create(['user_id' => $u->id, 'name' => 'private.png', 'path' => 'media/private.png', 'mime' => 'image/png', 'size' => 100, 'alt' => 'Private gallery image', 'is_public' => false]);
        $this->actingAs($u)->post('/admin/contents', $this->payload(['gallery_ids' => [$m->id]]))->assertSessionHasErrors('gallery_ids.0');
        $m->update(['is_public' => true]);
        $this->post('/admin/contents', $this->payload(['gallery_ids' => [$m->id], 'status' => 'published']))->assertRedirect();
        auth()->logout();
        $this->get('/ms/content/a-public-service')->assertOk()->assertSee('Private gallery image');
        $m->update(['is_public' => false]);
        $this->get('/ms/content/a-public-service')->assertOk()->assertDontSee('Private gallery image');
    }

    public function test_settings_and_navigation_save_and_render(): void
    {
        $this->actingAs($this->staff())->put('/admin/settings', ['title_ms' => 'Portal Ujian', 'title_en' => 'Test Portal', 'hero_ms' => 'Maklumat awam', 'hero_en' => 'Public information', 'description_ms' => 'Penerangan', 'description_en' => 'Description', 'email' => 'info@example.gov.my', 'phone' => '03-12345678', 'address' => 'Putrajaya'])->assertRedirect();
        $this->put('/admin/menus', ['locale' => 'ms', 'items' => [['label' => 'Lihat berita', 'url' => '/ms/search?type=post']]])->assertRedirect();
        auth()->logout();
        $this->get('/ms')->assertOk()->assertSee('Portal Ujian')->assertSee('Lihat berita');
    }
}
