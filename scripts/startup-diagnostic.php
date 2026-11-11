<?php

require __DIR__.'/../vendor/autoload.php';

$prefix = 'MIAMALA_STARTUP_DIAGNOSTIC';
$expected = 'https://miamala.onrender.com';

function reportValue(string $prefix, string $label, mixed $value, string $expected): void
{
    if (! is_string($value)) {
        printf("%s %s available=no\n", $prefix, $label);

        return;
    }

    $edgeWhitespace = $value !== trim($value)
        || preg_match('/(?:\A\s)|(?:\s\z)/u', $value) === 1;

    printf(
        "%s %s available=yes exact_match=%s bytes=%d CR=%s LF=%s TAB=%s other_ascii_control=%s edge_whitespace=%s\n",
        $prefix,
        $label,
        $value === $expected ? 'yes' : 'no',
        strlen($value),
        str_contains($value, "\r") ? 'yes' : 'no',
        str_contains($value, "\n") ? 'yes' : 'no',
        str_contains($value, "\t") ? 'yes' : 'no',
        preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1 ? 'yes' : 'no',
        $edgeWhitespace ? 'yes' : 'no',
    );
}

foreach ([
    'getenv.APP_URL' => getenv('APP_URL'),
    '_ENV.APP_URL' => $_ENV['APP_URL'] ?? false,
    '_SERVER.APP_URL' => $_SERVER['APP_URL'] ?? false,
    'getenv.ASSET_URL' => getenv('ASSET_URL'),
    'laravel_env.APP_URL' => \Illuminate\Support\Env::get('APP_URL'),
] as $label => $value) {
    reportValue($prefix, $label, $value, $expected);
}

$cachePath = __DIR__.'/../bootstrap/cache/config.php';

if (! is_file($cachePath)) {
    printf("%s compiled_config_exists=no\n", $prefix);
} else {
    printf("%s compiled_config_exists=yes\n", $prefix);

    try {
        $compiledConfig = @require $cachePath;
        $appUrl = is_array($compiledConfig)
            ? ($compiledConfig['app']['url'] ?? null)
            : null;

        reportValue($prefix, 'compiled_config.app.url', $appUrl, $expected);
    } catch (Throwable) {
        printf("%s compiled_config.app.url available=no load_error=yes\n", $prefix);
    }
}
