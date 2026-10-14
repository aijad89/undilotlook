<?php

/**
 * Run a fixture script with Laravel booted, and FAIL LOUDLY.
 *
 *     php tests/e2e/fixtures/run.php <script.php> [args…]
 *
 * `artisan tinker` exits 0 even when the script throws, and so does a bare
 * require once the console kernel's exception handler is installed. A fixture
 * that died half-way then looks like one that finished, and the spec fails some
 * minutes later on missing rows. Here the throw becomes exit(1), so the spec's
 * `beforeAll` fails and names the fixture as the cause.
 *
 * THE FENCE: it refuses to run against any database but the e2e copy. A fixture
 * writing into the development database while the browser reads the copy is the
 * failure that cost `daftar` eighteen red cases, twice. A comment did not stop it
 * the second time; this does.
 */
$root = dirname(__DIR__, 3);

if ($argc < 2) {
    fwrite(STDERR, "usage: php tests/e2e/fixtures/run.php <script.php> [args…]\n");
    exit(2);
}

$path = __DIR__.'/'.basename($argv[1]);

if (! is_file($path)) {
    fwrite(STDERR, "fixture not found: {$argv[1]}\n");
    exit(2);
}

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$database = config('database.connections.mysql.database');

fwrite(STDOUT, sprintf("fixture %s -> database '%s' (APP_ENV=%s)%s", basename($path), $database, $app->environment(), PHP_EOL));

if ($app->environment() !== 'e2e' || ! str_ends_with((string) $database, '_e2e')) {
    fwrite(STDERR, PHP_EOL."FIXTURE REFUSED: {$argv[1]} would write to '{$database}', which is not the e2e copy.".PHP_EOL
        .'  Run the suite through playwright.config.js, which sets APP_ENV and DB_DATABASE for every child.'.PHP_EOL.PHP_EOL);
    exit(3);
}

$args = array_slice($argv, 2);

try {
    require $path;
} catch (Throwable $e) {
    fwrite(STDERR, PHP_EOL.'FIXTURE FAILED: '.basename($path).PHP_EOL
        .get_class($e).': '.$e->getMessage().PHP_EOL
        .'  at '.$e->getFile().':'.$e->getLine().PHP_EOL);
    exit(1);
}
