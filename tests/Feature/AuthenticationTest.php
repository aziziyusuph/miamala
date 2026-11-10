<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('_token', false);
    }

    public function test_registration_page_loads_with_a_csrf_field(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('_token', false);
    }

    public function test_registration_form_trusts_forwarded_protocol_but_not_host(): void
    {
        $response = $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'attacker.example',
        ])->get('/register');

        $response->assertOk();
        $response->assertSee('action="https://', false);
        $response->assertDontSee('attacker.example', false);
    }

    public function test_registration_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/register', []);
        }

        $this->post('/register', [])->assertTooManyRequests();
        $this->assertDatabaseCount('businesses', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rate_limits_a_session_without_sharing_limits_between_emails(): void
    {
        $this->withCookie(config('session.cookie'), str_repeat('a', 40));

        foreach (range(1, 10) as $attempt) {
            $this->post('/register', ['email' => "person{$attempt}@example.com"]);
        }

        $this->post('/register', ['email' => 'another@example.com'])->assertTooManyRequests();
        $this->assertDatabaseCount('businesses', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_allows_different_users_from_the_same_ip(): void
    {
        foreach (['asha', 'neema'] as $name) {
            $email = "{$name}@example.com";
            $response = $this->post('/register', [
                'name' => ucfirst($name),
                'email' => $email,
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertDatabaseHas('users', ['email' => $email]);
            $this->post('/logout')->assertRedirect(route('login'));
        }

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('businesses', 2);
    }

    public function test_successful_registration_creates_a_business_and_authenticates_the_user(): void
    {
        $existingUser = User::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Asha Mollel',
            'business_name' => 'Asha Traders',
            'business_id' => $existingUser->business_id,
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $user = User::where('email', 'asha@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->business_id);
        $this->assertNotSame($existingUser->business_id, $user->business_id);
        $this->assertSame('Asha Traders', $user->business->name);
        $this->assertSame($user->id, $user->business->users()->sole()->id);
        $this->assertDatabaseHas('businesses', ['id' => $user->business_id]);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertNotSame('secure-password', $user->password);
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        User::factory()->create(['email' => 'asha@example.com']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Another Asha',
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email' => 'The email has already been taken.']);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('businesses', 1);
    }

    public function test_registration_normalizes_email_before_uniqueness_validation(): void
    {
        $this->post('/register', [
            'name' => 'Asha Mollel',
            'email' => '  ASHA@Example.com  ',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        $response = $this->from('/register')->post('/register', [
            'name' => 'Another Asha',
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'asha@example.com']);
    }

    public function test_registration_rejects_a_case_variant_of_a_legacy_email(): void
    {
        User::factory()->create(['email' => 'Asha@Example.com']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Another Asha',
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'Asha@Example.com']);
    }

    public function test_registration_rejects_a_mismatched_password_confirmation(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Asha Mollel',
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
        $this->assertGuest();
    }

    public function test_registration_rejects_missing_required_fields(): void
    {
        $response = $this->from('/register')->post('/register', []);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_registration_regenerates_the_session_id(): void
    {
        $this->withSession(['registration_probe' => true]);
        $sessionId = $this->app['session']->driver()->getId();

        $this->post('/register', [
            'name' => 'Asha Mollel',
            'email' => 'asha@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $this->assertNotSame($sessionId, $this->app['session']->driver()->getId());
    }

    public function test_failed_registration_rolls_back_business_creation(): void
    {
        User::creating(fn () => throw new RuntimeException('Simulated user persistence failure.'));
        $this->withoutExceptionHandling();

        try {
            $this->post('/register', [
                'name' => 'Asha Mollel',
                'email' => 'asha@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ]);
            $this->fail('The simulated persistence failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated user persistence failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
        $this->assertGuest();
    }

    public function test_valid_credentials_authenticate_and_redirect(): void
    {
        $user = User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_respects_an_intended_url(): void
    {
        $user = User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response = $this->withSession(['url.intended' => route('transactions.index')])
            ->post('/login', [
                'email' => 'asha@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect(route('transactions.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'asha@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => 'asha@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_failure_limits_are_normalized_by_email_and_isolated_between_users_on_the_same_ip(): void
    {
        $lockedUser = User::factory()->create(['email' => 'asha@example.com']);
        $otherUser = User::factory()->create([
            'email' => 'neema@example.com',
            'password' => 'password',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => 'ASHA@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => $lockedUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->post('/login', [
            'email' => $otherUser->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($otherUser);
    }

    public function test_login_normalizes_email_before_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'Asha@Example.com',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => '  ASHA@Example.com  ',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_uses_exact_legacy_email_when_case_variant_duplicates_exist(): void
    {
        $legacyUser = User::factory()->create([
            'email' => 'Asha@Example.com',
            'password' => 'legacy-password',
        ]);
        User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'other-password',
        ]);

        $this->post('/login', [
            'email' => 'Asha@Example.com',
            'password' => 'legacy-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($legacyUser);
    }

    public function test_authenticated_user_is_available_through_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, auth()->id());
    }

    public function test_logout_invalidates_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
