<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Media;
use Database\Seeders\PmoSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmoSampleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_samples_render_with_sources_and_locally_served_photos(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 11, 26));
        Storage::fake('local');
        $this->seed(PmoSampleSeeder::class);

        $this->assertDatabaseCount('contents', 4);
        $this->assertDatabaseCount('media', 2);
        $this->assertSame(2, Content::where('type', 'speech')->count());
        foreach (Content::all() as $content) {
            $this->get($content->url())->assertOk()->assertSee('CONTOH')->assertSee('https://www.pmo.gov.my/ms/');
        }
        foreach (Media::all() as $media) {
            Storage::disk('local')->assertExists($media->path);
            $this->assertSame('image/jpeg', getimagesize(Storage::disk('local')->path($media->path))['mime']);
            $this->get(route('media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        }
    }

    public function test_reseeding_preserves_edits_trash_and_media_visibility(): void
    {
        Storage::fake('local');
        $this->seed(PmoSampleSeeder::class);
        $edited = Content::where('type', 'speech')->firstOrFail();
        $edited->update(['title' => 'Editorial revision', 'status' => 'draft']);
        $trashed = Content::where('type', 'gallery')->firstOrFail();
        $trashed->delete();
        Media::query()->update(['is_public' => false]);

        $this->seed(PmoSampleSeeder::class);

        $this->assertDatabaseCount('contents', 4);
        $this->assertDatabaseCount('media', 2);
        $this->assertSame('Editorial revision', $edited->fresh()->title);
        $this->assertSame('draft', $edited->fresh()->status);
        $this->assertSoftDeleted($trashed);
        $this->assertSame(0, Media::where('is_public', true)->count());
    }

    public function test_production_seeding_is_rejected(): void
    {
        $this->app->instance('env', 'production');
        try {
            $this->seed(PmoSampleSeeder::class);
            $this->fail('Production must reject sample seeding.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('PMO sample data is disabled in production.', $exception->getMessage());
            $this->assertDatabaseCount('contents', 0);
            $this->assertDatabaseCount('users', 0);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }
}
