<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Content;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PmoSampleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('PMO sample data is disabled in production.');
        }

        $samples = json_decode(file_get_contents(__DIR__.'/fixtures/pmo/samples.json'), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($samples) {
            $user = User::firstOrCreate(['email' => 'pmo-samples@example.invalid'], [
                'name' => 'PMO sample content', 'password' => Str::random(60), 'active' => false,
            ]);
            $category = Category::firstOrCreate(['slug' => 'pmo-samples'], ['name' => 'Contoh daripada portal PMO']);

            foreach ($samples as $sample) {
                // Respect existing edits and deliberately trashed sample publications.
                if (Content::withTrashed()->where('locale', 'ms')->where('slug', $sample['slug'])->exists()) {
                    continue;
                }

                $media = null;
                if (isset($sample['image'])) {
                    $image = $sample['image'];
                    $path = 'media/pmo-samples/'.$image['file'];
                    if (! Storage::disk('local')->exists($path)) {
                        $bytes = file_get_contents(__DIR__.'/fixtures/pmo/'.$image['file']);
                        if (! Storage::disk('local')->put($path, $bytes)) {
                            throw new \RuntimeException('Unable to store PMO sample image.');
                        }
                    }
                    $media = Media::firstOrCreate(['path' => $path], [
                        'user_id' => $user->id, 'name' => $image['file'], 'mime' => 'image/jpeg',
                        'size' => Storage::disk('local')->size($path), 'alt' => $image['alt'], 'is_public' => true,
                    ]);
                }

                $body = '<p><strong>CONTOH — Ringkasan untuk demonstrasi portal, bukan teks rasmi penuh.</strong></p>'
                    .'<p>'.e($sample['summary']).'</p>'
                    .'<p>Sumber: Pejabat Perdana Menteri Malaysia. <a href="'.e($sample['source_url']).'">'
                    .($sample['type'] === 'speech' ? 'Baca teks ucapan penuh di portal PMO' : 'Lihat penerbitan asal di portal PMO').'</a>.</p>';
                if (isset($sample['image'])) {
                    $body .= '<p>Kredit gambar: '.e($sample['image']['credit']).'.</p>';
                }

                Content::create([
                    'slug' => $sample['slug'], 'locale' => 'ms', 'user_id' => $user->id,
                    'category_id' => $category->id, 'type' => $sample['type'],
                    'title' => '[Contoh] '.$sample['title'], 'excerpt' => $sample['summary'],
                    'body' => $body, 'status' => 'published', 'published_at' => $sample['published_at'],
                    'media_id' => $media?->id,
                    'gallery_ids' => $sample['type'] === 'gallery' && $media ? [$media->id] : null,
                    'tags' => 'contoh,pmo',
                ]);
            }
        });
    }
}
