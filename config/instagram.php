<?php

return [
    'enabled' => env('PORTAL_INSTAGRAM_ENABLED', true),
    'profile_url' => 'https://www.instagram.com/malaysiapmo/',
    // Curated links verified on the PMO homepage on 26 November 2026.
    // These are not a live feed; keep thumbnails local to avoid expiring CDN URLs.
    'posts' => [
        [
            'url' => 'https://www.instagram.com/reel/Db1rw9ThFB5/',
            'image' => 'images/instagram/Db1rw9ThFB5.jpg',
            'type' => 'reel',
            'title_ms' => 'Ulang tahun kelahiran Perdana Menteri',
            'title_en' => 'Prime Minister’s birthday',
        ],
        [
            'url' => 'https://www.instagram.com/reel/DWD4BsaAarn/',
            'image' => 'images/instagram/DWD4BsaAarn.jpg',
            'type' => 'reel',
            'title_ms' => 'Salam Aidilfitri daripada warga PMO',
            'title_en' => 'Eid greetings from PMO',
        ],
        [
            'url' => 'https://www.instagram.com/reel/DRa6rt7kyeq/',
            'image' => 'images/instagram/DRa6rt7kyeq.jpg',
            'type' => 'reel',
            'title_ms' => 'Ekonomi dan tatakelola untuk rakyat',
            'title_en' => 'Economy and governance for the people',
        ],
        [
            'url' => 'https://www.instagram.com/p/DQzA4gMgcDH/',
            'image' => 'images/instagram/DQzA4gMgcDH.jpg',
            'type' => 'carousel',
            'title_ms' => 'Projek trafik Juru–Sungai Dua',
            'title_en' => 'Juru–Sungai Dua traffic project',
        ],
        [
            'url' => 'https://www.instagram.com/p/DQzAig9AekL/',
            'image' => 'images/instagram/DQzAig9AekL.jpg',
            'type' => 'carousel',
            'title_ms' => 'Penutup Festival Idea Putrajaya',
            'title_en' => 'Putrajaya ideas festival closing',
        ],
        [
            'url' => 'https://www.instagram.com/p/DQzAHj8AUqj/',
            'image' => 'images/instagram/DQzAHj8AUqj.jpg',
            'type' => 'carousel',
            'title_ms' => 'Pembangunan reka bentuk semikonduktor',
            'title_en' => 'Semiconductor design development',
        ],
        [
            'url' => 'https://www.instagram.com/p/DQn23B4gX-X/',
            'image' => 'images/instagram/DQn23B4gX-X.jpg',
            'type' => 'image',
            'title_ms' => 'Maklumat subsidi BUDI95',
            'title_en' => 'BUDI95 subsidy information',
        ],
        [
            'url' => 'https://www.instagram.com/p/DQiaywBAX2u/',
            'image' => 'images/instagram/DQiaywBAX2u.jpg',
            'type' => 'image',
            'title_ms' => 'Makluman lawatan kerja Pahang',
            'title_en' => 'Pahang working visit notice',
        ],
    ],
];
