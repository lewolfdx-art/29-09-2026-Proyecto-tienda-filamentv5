<?php

namespace Tests\Feature;

use App\Support\DatabaseBackupZip;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Tests\TestCase;
use ZipArchive;

class DatabaseBackupZipTest extends TestCase
{
    public function test_it_dumps_the_database_into_an_encrypted_zip(): void
    {
        if ((new ExecutableFinder)->find('sqlite3') === null) {
            $this->markTestSkipped('El volcado de SQLite necesita el binario sqlite3.');
        }

        $directory = storage_path('framework/testing');
        File::ensureDirectoryExists($directory);
        $database = $directory.DIRECTORY_SEPARATOR.'backup-zip-test.sqlite';
        File::put($database, '');

        $pdo = new \PDO('sqlite:'.$database);
        $pdo->exec('CREATE TABLE marcas (nombre TEXT)');
        $pdo->exec("INSERT INTO marcas VALUES ('fila-de-prueba')");
        $pdo = null;

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $database,
            'backup.backup.password' => 'secreto-de-prueba',
        ]);

        $zipPath = null;

        try {
            $zipPath = (new DatabaseBackupZip)->create();

            $this->assertFileExists($zipPath);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($zipPath));
            $this->assertSame(ZipArchive::EM_AES_256, $zip->statName('database.sql')['encryption_method']);

            $zip->setPassword('secreto-de-prueba');
            $sql = $zip->getFromName('database.sql');
            $zip->close();

            $this->assertIsString($sql);
            $this->assertStringContainsString('fila-de-prueba', $sql);
        } finally {
            File::delete(array_filter([$database, $zipPath]));
        }
    }
}
