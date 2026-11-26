<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramTest extends TestCase
{
    use RefreshDatabase;

    public function test_instagram_section_can_be_disabled_without_removing_other_content(): void
    {
        $this->withoutVite();
        config(['instagram.enabled' => false]);

        $this->get('/ms')->assertOk()->assertDontSee('instagram-heading')->assertSee('hero-search');
    }
}
