<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BusinessSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_registered_user_can_log_in_and_complete_business_setup(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Asha Mushi',
            'email' => 'asha@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect(route('login'));

        $user = User::query()->where('email', 'asha@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertGuest();
        Notification::assertNotSentTo($user, VerifyEmail::class);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'StrongPass123',
        ])->assertRedirect(route('business.setup'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('business.setup'))
            ->assertOk()
            ->assertSee('Set up your business');

        $this->post(route('business.setup.store'), [
            'business_name' => 'Asha Traders',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Asha Traders', $user->fresh()->business->name);
        $this->assertSame(1, Business::query()->count());
        $this->get('/dashboard')->assertOk();
    }

    public function test_guests_cannot_access_business_setup_or_create_a_business(): void
    {
        $this->get(route('business.setup'))->assertRedirect(route('login'));

        $this->post(route('business.setup.store'), [
            'business_name' => 'Unauthorized Business',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_setup_validates_name_and_does_not_create_on_invalid_input(): void
    {
        $user = User::factory()->unverified()->create(['business_id' => null]);

        $this->actingAs($user)
            ->from(route('business.setup'))
            ->post(route('business.setup.store'), ['business_name' => str_repeat('a', 256)])
            ->assertRedirect(route('business.setup'))
            ->assertSessionHasErrors('business_name');

        $this->assertNull($user->fresh()->business_id);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_user_with_a_business_cannot_replace_it_through_business_setup(): void
    {
        $user = User::factory()->unverified()->create();
        $businessId = $user->business_id;

        $this->actingAs($user)
            ->get(route('business.setup'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($businessId, $user->fresh()->business_id);
        $this->assertSame(1, Business::query()->count());
    }

    public function test_business_setup_ignores_forged_user_and_business_ids(): void
    {
        $otherUser = User::factory()->create();
        $otherBusinessId = $otherUser->business_id;
        $onboardingUser = User::factory()->unverified()->create(['business_id' => null]);

        $this->actingAs($onboardingUser)
            ->post(route('business.setup.store'), [
                'business_name' => 'Asha Traders',
                'user_id' => $otherUser->id,
                'business_id' => $otherBusinessId,
            ])->assertRedirect(route('dashboard'));

        $onboardingUser->refresh();
        $otherUser->refresh();
        $this->assertNotSame($otherBusinessId, $onboardingUser->business_id);
        $this->assertSame($otherBusinessId, $otherUser->business_id);
        $this->assertSame('Asha Traders', $onboardingUser->business->name);
        $this->assertSame(2, Business::query()->count());
    }
}
