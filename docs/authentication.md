# Authentication and email configuration

Registration provisions a new business workspace and its first user in a single database transaction. The new user is attached through the existing `Business::users()` relationship; existing demo account and ownership provisioning behavior is unchanged.

Laravel's password-reset broker uses the existing `password_reset_tokens` table and the application's configured mailer. Reset tokens expire after the configured 60-minute period and the broker throttles repeated link requests. The default development mail configuration writes to the log; configure an actual outbound mail provider before enabling password recovery in production.

## Render environment

Set the existing Laravel mail and application environment variables in Render's environment configuration. Use provider-issued values and keep credentials in Render secrets; never commit them to the repository.

- `APP_URL`: the canonical HTTPS URL for the deployed application (for example, `https://miamala.onrender.com`) so generated reset links use HTTPS.
- `MAIL_MAILER`: the configured Laravel mail transport, such as `smtp` or a supported provider transport.
- `MAIL_SCHEME`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`: set the values required by the selected mail provider; do not set credentials in source files.
- `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`: a sender identity accepted by the provider.

No new environment variables are required by the authentication code. Continue to configure the PostgreSQL connection and `APP_KEY` as already required by the deployment. Ensure the provider permits outbound email from the application and verify a reset email after deployment. No deployment or production configuration changes are performed by this feature.
