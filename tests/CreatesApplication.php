<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Tes mengosongkan database (RefreshDatabase, ConcurrencyTest). Jangan pernah jalan di database utama.
        $database = (string) $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database');

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException("Tes dihentikan: database '{$database}' bukan database tes. "
                .'Atur DB_DATABASE di phpunit.xml ke database berakhiran _test.');
        }

        return $app;
    }
}
