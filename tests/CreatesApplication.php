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

        // Pengaman: jangan pernah menjalankan pengujian (yang mengosongkan
        // basis data) terhadap selain basis data *_test. Konfigurasi yang
        // ter-cache bisa membuat PHPUnit mengabaikan phpunit.xml.
        $koneksi = $app['config']->get('database.default');
        $nama = (string) $app['config']->get("database.connections.{$koneksi}.database");
        if (! str_ends_with($nama, '_test')) {
            throw new RuntimeException("Pengujian dibatalkan: basis data \"{$nama}\" bukan basis data uji. Jalankan `php artisan config:clear` terlebih dahulu.");
        }

        return $app;
    }
}
