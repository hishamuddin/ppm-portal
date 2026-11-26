<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Content;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo data is disabled in production.');
        }
        $user = User::firstOrCreate(['email' => 'demo-author@example.invalid'], ['name' => 'Demo content author', 'password' => Str::random(60)]);
        $user->active = false;
        $user->save();
        $category = Category::firstOrCreate(['slug' => 'announcements'], ['name' => 'Pengumuman / Announcements']);
        foreach (['ms', 'en'] as $locale) {
            $titles = $locale === 'ms' ? ['Selamat datang ke portal baharu', 'Maklumat korporat', 'Kenyataan media — contoh', 'Ucapan — contoh', 'Galeri aktiviti — contoh'] : ['Welcome to the new portal', 'Corporate information', 'Media statement — sample', 'Speech — sample', 'Activity gallery — sample'];
            foreach ($titles as $i => $title) {
                Content::firstOrCreate(['slug' => ['welcome', 'corporate', 'statement-demo', 'speech-demo', 'gallery-demo'][$i], 'locale' => $locale], ['user_id' => $user->id, 'category_id' => $category->id, 'type' => ['post', 'page', 'statement', 'speech', 'gallery'][$i], 'title' => $title, 'excerpt' => $locale === 'ms' ? 'Kandungan demonstrasi untuk semakan pasukan editorial.' : 'Demonstration content for editorial review.', 'body' => '<p>DEMO — This is sample content, not an official government publication. Replace it with approved material before launch.</p>', 'status' => 'published', 'published_at' => now()->subDays($i)]);
            }
        }
        Setting::firstOrCreate(['key' => 'portal'], ['value' => ['title_ms' => 'Pejabat Perdana Menteri', 'title_en' => 'Prime Minister’s Office', 'hero_ms' => 'Maklumat yang jelas. Kerajaan yang dekat.', 'hero_en' => 'Clear information. A government closer to you.', 'description_ms' => 'Akses berita, ucapan dan maklumat Pejabat Perdana Menteri dalam satu portal.', 'description_en' => 'Discover news, speeches and information from the Prime Minister’s Office in one place.', 'email' => 'webmaster@pmo.gov.my', 'phone' => '03-8888 8000', 'address' => 'Bangunan Perdana Putra, Pusat Pentadbiran Kerajaan Persekutuan, 62502 Putrajaya, Malaysia']]);
        Setting::firstOrCreate(['key' => 'menus'], ['value' => ['ms' => [['label' => 'Pejabat PM', 'url' => '/ms/content/corporate'], ['label' => 'Berita', 'url' => '/ms/search?type=post'], ['label' => 'Ucapan', 'url' => '/ms/search?type=speech'], ['label' => 'Kenyataan Media', 'url' => '/ms/search?type=statement'], ['label' => 'Galeri', 'url' => '/ms/search?type=gallery']], 'en' => [['label' => 'About the office', 'url' => '/en/content/corporate'], ['label' => 'News', 'url' => '/en/search?type=post'], ['label' => 'Speeches', 'url' => '/en/search?type=speech'], ['label' => 'Media statements', 'url' => '/en/search?type=statement'], ['label' => 'Gallery', 'url' => '/en/search?type=gallery']]]]);
    }
}
