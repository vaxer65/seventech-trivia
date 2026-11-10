<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Candado: RefreshDatabase borra todas las tablas. Nunca debe correr sobre la base real.
     */
    protected function beforeRefreshingDatabase()
    {
        $base = (string) DB::connection()->getDatabaseName();

        if (!str_ends_with($base, '_test')) {
            throw new RuntimeException("Tests abortados: la base '{$base}' no termina en _test.");
        }
    }
}
