<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_malay_portal(): void
    {
        $this->get('/')->assertRedirect('/ms');
    }
}
