<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->assertSame('Asha Mushi\'s Business', $user->business->name);
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

        $this->actingAs($user)->get('/logout')->assertNotFound();
    }
}
