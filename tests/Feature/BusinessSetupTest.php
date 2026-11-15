<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BusinessSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_verification_setup_and_login_follow_the_required_journey(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Asha Mushi',
            'email' => 'ASHA@example.com',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'asha@example.com')->firstOrFail();
        $this->assertGuest();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($user->business_id);
        $this->assertDatabaseCount('businesses', 0);

        $verificationUrl = $this->verificationUrlFromNotification($user);
        parse_str((string) parse_url($verificationUrl, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('expires', $query);
        $this->assertArrayHasKey('signature', $query);

        $this->get($verificationUrl)->assertRedirect(route('business.setup'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertGuest();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('business.setup'))->assertOk()->assertSee('Set up your business');

        $this->post(route('business.setup.store'), [
            'business_name' => 'Asha Traders',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertSame('Asha Traders', $user->business->name);
        $this->assertSame(1, Business::query()->count());
        $this->assertGuest();

        $this->post(route('business.setup.store'), [
            'business_name' => 'A second business',
        ])->assertRedirect(route('onboarding.resume'));
        $this->assertSame(1, Business::query()->count());

        $this->post('/login', [
            'email' => 'ASHA@example.com',
            'password' => 'StrongPass123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
    }

    public function test_unverified_users_cannot_access_business_setup_or_dashboard(): void
    {
        $user = User::factory()->unverified()->create(['business_id' => null]);

        $this->get(route('business.setup'))
            ->assertRedirect(route('onboarding.resume'));

        $this->post(route('business.setup.store'), [
            'business_name' => 'Unauthorized Business',
        ])->assertRedirect(route('onboarding.resume'));

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_invalid_or_expired_verification_links_do_not_verify_or_grant_setup_access(): void
    {
        $user = User::factory()->unverified()->create(['business_id' => null]);

        $expiredUrl = $this->verificationUrl($user, now()->subMinute());
        $this->get($expiredUrl)->assertForbidden();

        $tamperedUrl = $this->verificationUrl($user, now()->addMinutes(30));
        $urlParts = parse_url($tamperedUrl);
        parse_str($urlParts['query'], $query);
        $query['signature'] = str_repeat('0', 64);
        $tamperedUrl = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.http_build_query($query);
        $this->get($tamperedUrl)->assertForbidden();
        $this->get(route('business.setup'))
            ->assertRedirect(route('onboarding.resume'));
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseCount('businesses', 0);

        $invalidHashUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1('different@example.com')],
        );
        $this->get($invalidHashUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_verified_user_can_recover_setup_access_after_the_session_is_lost(): void
    {
        $user = User::factory()->unverified()->create(['business_id' => null]);
        $this->get($this->verificationUrl($user, now()->addMinutes(30)))
            ->assertRedirect(route('business.setup'));
        $this->get(route('business.setup'))->assertOk();

        $this->flushSession()->get(route('business.setup'))
            ->assertRedirect(route('onboarding.resume'));

        Notification::fake();
        $this->post(route('verification.send'), ['email' => $user->email])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status');

        $resumeUrl = $this->verificationUrlFromNotification($user);
        $this->get($resumeUrl)->assertRedirect(route('business.setup'));
        $this->get(route('business.setup'))->assertOk();
    }

    public function test_business_setup_validates_name_and_does_not_create_on_invalid_input(): void
    {
        $user = User::factory()->unverified()->create(['business_id' => null]);
        $this->get($this->verificationUrl($user, now()->addMinutes(30)));

        $this->from(route('business.setup'))
            ->post(route('business.setup.store'), ['business_name' => str_repeat('a', 256)])
            ->assertRedirect(route('business.setup'))
            ->assertSessionHasErrors('business_name');

        $this->assertNull($user->fresh()->business_id);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_verifying_an_existing_business_user_preserves_their_business(): void
    {
        $user = User::factory()->unverified()->create();
        $businessId = $user->business_id;

        $this->get($this->verificationUrl($user, now()->addMinutes(30)))
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame($businessId, $user->business_id);
        $this->assertSame(1, Business::query()->count());
    }

    public function test_business_setup_ignores_forged_user_and_business_ids(): void
    {
        $otherUser = User::factory()->create();
        $otherBusinessId = $otherUser->business_id;
        $onboardingUser = User::factory()->unverified()->create(['business_id' => null]);

        $this->get($this->verificationUrl($onboardingUser, now()->addMinutes(30)))
            ->assertRedirect(route('business.setup'));

        $this->post(route('business.setup.store'), [
            'business_name' => 'Asha Traders',
            'user_id' => $otherUser->id,
            'business_id' => $otherBusinessId,
        ])->assertRedirect(route('login'));

        $onboardingUser->refresh();
        $otherUser->refresh();
        $this->assertNotSame($otherBusinessId, $onboardingUser->business_id);
        $this->assertSame($otherBusinessId, $otherUser->business_id);
        $this->assertSame('Asha Traders', $onboardingUser->business->name);
        $this->assertSame(2, Business::query()->count());
    }

    private function verificationUrl(User $user, DateTimeInterface $expiration): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiration,
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
        );
    }

    private function verificationUrlFromNotification(User $user): string
    {
        $verificationUrl = null;

        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$verificationUrl): bool {
            $verificationUrl = $notification->toMail($user)->actionUrl;

            return true;
        });

        $this->assertIsString($verificationUrl);

        return $verificationUrl;
    }
}
