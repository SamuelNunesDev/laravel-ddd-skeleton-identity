<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class HomeTest extends TestCase
{
    public function test_home_page_is_rendered_by_inertia(): void
    {
        $this->withoutVite();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Home'));
    }
}
