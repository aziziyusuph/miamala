<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads_with_csrf_and_auth_links(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee(route('password.request'), false);
        $response->assertSee(route('register'), false);
        $response->assertSee('data-password-toggle', false);
    }

    public function test_registration_creates_user_and_owned_business_and_authenticates(): void
    {
        $response = $this->post('/register', [
            'name' => 'Asha Mushi',
            'email' => 'ASHA@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ]);

        $user = User::query()->where('email', 'asha@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->business_id);
        $this->assertSame('Asha Mushi Workspace', $user->business->name);
        $this->assertDatabaseHas('businesses', ['id' => $user->business_id]);
        $this->assertTrue(Hash::check('StrongPass123', $user->password));
        $this->assertNotSame('StrongPass123', $user->password);
    }

    public function test_duplicate_email_registration_is_rejected(): void
    {
        User::factory()->create(['email' => 'asha@example.com']);

        $this->from('/register')->post('/register', [
            'name' => 'Asha',
            'email' => 'asha@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('businesses', 1);
    }

    public function test_registration_validates_required_data_password_length_and_confirmation(): void
    {
        $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertRedirect('/register')->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_registration_page_renders_password_visibility_controls(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
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
            $this->post('/logout')->assertRedirect(route('landing'));
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
        $user = User::factory()->create(['email' => 'asha@example.com', 'password' => 'password']);

        $response = $this->post('/login', ['email' => 'asha@example.com', 'password' => 'password']);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_respects_an_intended_url_and_remember_me(): void
    {
        $user = User::factory()->create(['email' => 'asha@example.com', 'password' => 'password']);

        $this->withSession(['url.intended' => route('transactions.index')])
            ->post('/login', ['email' => 'asha@example.com', 'password' => 'password', 'remember' => '1'])
            ->assertRedirect(route('transactions.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_a_generic_error(): void
    {
        User::factory()->create(['email' => 'asha@example.com', 'password' => 'password']);

        $this->from('/login')->post('/login', ['email' => 'unknown@example.com', 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['email' => 'asha@example.com', 'password' => 'password']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard_and_authenticated_user_can_open_it(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_forgot_password_sends_native_reset_notification_and_does_not_disclose_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'asha@example.com']);

        $response = $this->post('/forgot-password', ['email' => $user->email]);
        $response->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        $genericStatus = session('status');
        $this->post('/forgot-password', ['email' => 'missing@example.com'])
            ->assertRedirect()->assertSessionHas('status', $genericStatus);
    }

    public function test_reset_link_page_renders_both_password_visibility_controls(): void
    {
        $this->get(route('password.reset', ['token' => 'example-token', 'email' => 'asha@example.com']))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_valid_reset_token_changes_password_and_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'asha@example.com', 'password' => 'old-password']);
        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertIsString($token);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ];
        $this->post('/reset-password', $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewStrongPass123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->from(route('password.reset', ['token' => $token]))
            ->post('/reset-password', $payload)
            ->assertRedirect(route('password.reset', ['token' => $token]))
            ->assertSessionHasErrors('email');

        $this->post('/login', ['email' => $user->email, 'password' => 'NewStrongPass123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_finds_a_legacy_mixed_case_email(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'Asha@Example.com',
            'password' => 'old-password',
        ]);

        $this->post('/forgot-password', ['email' => 'asha@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertIsString($token);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'asha@example.com',
            'password' => 'NewStrongPass123',
            'password_confirmation' => 'NewStrongPass123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewStrongPass123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
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

    public function test_login_failures_from_another_ip_do_not_lock_out_the_user(): void
    {
        $user = User::factory()->create([
            'email' => 'asha@example.com',
            'password' => 'password',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])->assertSessionHasErrors('email');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
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

    public function test_invalid_or_expired_reset_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['email' => 'asha@example.com']);
        $token = app('auth.password')->broker()->createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subMinutes(61)]);

        $this->from(route('password.reset', ['token' => $token]))
            ->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewStrongPass123',
                'password_confirmation' => 'NewStrongPass123',
            ])
            ->assertRedirect(route('password.reset', ['token' => $token]))
            ->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('NewStrongPass123', $user->fresh()->password));
    }

    public function test_logout_invalidates_authentication_and_returns_to_homepage(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('status', 'You have been securely signed out.');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_logout_route_requires_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/logout')->assertMethodNotAllowed();
    }
}
