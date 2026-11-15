# Authentication and onboarding

## Registration and onboarding

The registration and onboarding flow is:

1. A new user submits their name, email address, and password.
2. Miamala creates an unverified user account. Registration does not sign the user in or create a business.
3. Miamala sends an email verification link.
4. After the user follows a valid verification link, Miamala grants that browser session restricted access to business setup.
5. The user submits a business name. Miamala creates and assigns the business to the verified user.
6. Miamala clears the temporary setup session and redirects the user to login.
7. The user signs in with the email address and password used at registration, then can access the dashboard.

Business setup is not normal authentication and does not grant dashboard access. Dashboard and transaction routes require an authenticated, verified user who has an associated business. Business setup accepts a business name only; the user ID and business assignment are derived server-side. Creation and assignment run in a database transaction, and an existing business assignment is reused rather than creating another business on repeated submissions.

## Verification and onboarding recovery

The verification notice is available at `/email/verify`; `/onboarding/resume` displays the same notice. If a user loses their browser session, closes the browser before finishing setup, or needs another link, they can visit either page, enter their email address, and submit the form. The form posts to `/email/verification-notification`. For an eligible account, Miamala sends a fresh verification/setup link. The response is generic and does not disclose whether an account exists.

The resend endpoint sends a link only when exactly one account matches the normalized email and that account is unverified or has not yet been assigned a business. After receiving a fresh link, an unverified user must verify their email again before setup; a verified user who has not completed setup can use the link to regain restricted setup access. A user who already has a business is directed to login after verification.

Verification URLs are signed, expire after 60 minutes (Laravel's default when no `auth.verification.expire` value is configured), and the verification endpoint is limited to 6 requests per minute. Resend requests are limited to 3 per hour per normalized email across IP addresses, with additional 3-per-hour email-and-IP and 10-per-hour IP limits. Business setup submissions are limited to 10 per minute per setup session. Requests exceeding a throttle receive HTTP 429.

The setup permission is held in the web session and is checked against the user's verification and business status on each setup request. If that session is lost or expires, the user can request another link using the recovery steps above. Repeated setup submissions do not create an additional business for a user who already has one.

## Password resets

Laravel's password-reset broker uses the `password_reset_tokens` table and the application's configured mailer. Reset tokens expire after 60 minutes, and the broker throttles repeated link requests.

## Production deployment requirements

Production onboarding requires the following deployment configuration:

- Set `APP_URL` to the canonical HTTPS URL for the application. In production, the application forces HTTPS for generated URLs.
- Set a strong, persistent `APP_KEY` and preserve it across deployments. Do not put its value in source control or documentation.
- Configure a real Laravel mail transport using the environment variables required by the selected provider. For SMTP, these commonly include `MAIL_MAILER`, `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, and `MAIL_PASSWORD`; other transports may require provider-specific settings.
- Set `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` to a sender identity accepted by the mail provider.
- When running multiple application instances, use a shared cache store so rate limits are enforced consistently across instances. Production startup requires the default cache store to be one of the configured shared stores: database, DynamoDB, Memcached, or Redis.

The configured default mailer is `log`; it writes messages to the application log rather than delivering them to recipients. It is not suitable for sending verification emails to real users. Configure and verify a production email transport and sender identity before onboarding real users.
