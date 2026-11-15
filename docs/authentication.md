# Authentication and onboarding

## Registration and onboarding

The registration and onboarding flow is:

1. A new user submits their name, email address, and password.
2. Miamala validates and creates the user account without sending an email or signing the user in.
3. Miamala redirects to login and displays: “Your account has been created successfully. You can now log in.”
4. The user signs in with the email address and password used at registration. Email verification is not required.
5. If the user does not have a business, Miamala directs them to authenticated business setup. After submitting a business name, they can access the dashboard.

Dashboard and transaction routes require an authenticated user with an associated business; email verification is not checked. Business setup accepts a business name only. The user ID and business assignment are derived server-side, and creation and assignment run in a database transaction. An existing business assignment is reused rather than creating another business on repeated submissions. Business setup submissions are limited to 10 per minute per session.

## Password resets

Laravel's password-reset broker uses the `password_reset_tokens` table and the application's configured mailer. Reset tokens expire after 60 minutes, and the broker throttles repeated link requests.

## Production deployment requirements

Production deployment requires the following configuration:

- Set `APP_URL` to the canonical HTTPS URL for the application. In production, the application forces HTTPS for generated URLs.
- Set a strong, persistent `APP_KEY` and preserve it across deployments. Do not put its value in source control or documentation.
- Configure a real Laravel mail transport and sender identity if password-reset emails need to be delivered. Registration and business setup do not send email or require mail-provider configuration.
- When running multiple application instances, use a shared cache store so rate limits are enforced consistently across instances. Production startup requires the default cache store to be one of the configured shared stores: database, DynamoDB, Memcached, or Redis.

The configured default mailer is `log`; it writes messages to the application log rather than delivering them to recipients. Configure a production email transport only when real password-reset delivery is required.
