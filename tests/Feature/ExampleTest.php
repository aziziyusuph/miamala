<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_homepage_has_a_create_account_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Create account')
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_logout_confirmation_is_visible_after_redirecting_to_homepage(): void
    {
        $user = User::factory()->create();

        $this->followingRedirects()
            ->actingAs($user)
            ->post('/logout')
            ->assertOk()
            ->assertSee('You have been securely signed out.')
            ->assertSee('role="status"', false);
    }
}
