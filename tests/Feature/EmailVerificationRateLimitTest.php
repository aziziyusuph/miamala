<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_limit_is_shared_across_ip_addresses_for_one_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'person@example.com']);
        Notification::fake();

        foreach (['192.0.2.1', '192.0.2.2', '192.0.2.3'] as $ipAddress) {
            $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                ->post(route('verification.send'), ['email' => $user->email])
                ->assertRedirect(route('verification.notice'))
                ->assertSessionHas('status', $this->genericResponse());
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.4'])
            ->post(route('verification.send'), ['email' => $user->email])
            ->assertTooManyRequests();

        Notification::assertSentTo($user, VerifyEmail::class, 3);
    }

    public function test_resend_limit_normalizes_email_case_and_surrounding_whitespace(): void
    {
        Notification::fake();

        foreach ([
            [' Person@Example.com ', '192.0.2.11'],
            ['person@EXAMPLE.com', '192.0.2.12'],
            ['PERSON@example.COM', '192.0.2.13'],
        ] as [$email, $ipAddress]) {
            $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                ->post(route('verification.send'), ['email' => $email])
                ->assertRedirect(route('verification.notice'))
                ->assertSessionHas('status', $this->genericResponse());
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.14'])
            ->post(route('verification.send'), ['email' => ' person@example.COM '])
            ->assertTooManyRequests();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_resend_limits_are_independent_for_different_email_addresses(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $attempt) {
            $this->post(route('verification.send'), ['email' => 'first@example.com'])
                ->assertRedirect(route('verification.notice'));
        }

        $this->post(route('verification.send'), ['email' => 'second@example.com'])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', $this->genericResponse());
    }

    public function test_resend_limit_retains_the_existing_per_ip_protection(): void
    {
        Notification::fake();

        foreach (range(1, 10) as $attempt) {
            $this->post(route('verification.send'), [
                'email' => "person{$attempt}@example.com",
            ])->assertRedirect(route('verification.notice'));
        }

        $this->post(route('verification.send'), ['email' => 'person11@example.com'])
            ->assertTooManyRequests();
    }

    public function test_resend_response_is_generic_for_unknown_and_existing_addresses(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'known@example.com']);
        Notification::fake();

        $knownResponse = $this->post(route('verification.send'), ['email' => $user->email]);
        $unknownResponse = $this->post(route('verification.send'), ['email' => 'unknown@example.com']);

        $knownResponse->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', $this->genericResponse());
        $unknownResponse->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', $this->genericResponse());

        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseCount('users', 1);
    }

    private function genericResponse(): string
    {
        return 'If an eligible account exists, a verification or setup link will be sent shortly.';
    }
}
