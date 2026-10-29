<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\Sqlite;
use ZipArchive;

class DatabaseBackupZip
{
    /**
     * Genera un zip cifrado que contiene únicamente un volcado de la base de datos.
     */
    public function create(): string
    {
        $password = (string) config('backup.backup.password');

        if ($password === '') {
            throw new RuntimeException('Falta BACKUP_ARCHIVE_PASSWORD para cifrar el backup.');
        }

        $directory = storage_path('app/backup-temp');
        File::ensureDirectoryExists($directory);

        $base = 'backup-db-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8));
        $sqlPath = $directory.DIRECTORY_SEPARATOR.$base.'.sql';
        $zipPath = $directory.DIRECTORY_SEPARATOR.$base.'.zip';

        try {
            $this->dumpDatabase($sqlPath);

            if (! File::exists($sqlPath) || File::size($sqlPath) === 0) {
                throw new RuntimeException('El volcado de la base de datos quedó vacío.');
            }

            $this->createEncryptedZip($sqlPath, $zipPath, $password);
        } finally {
            if (File::exists($sqlPath)) {
                File::delete($sqlPath);
            }
        }

        return $zipPath;
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            throw new RuntimeException("No se encontró la configuración de la conexión {$connection}.");
        }

        if ($connection === 'mysql') {
            $dumper = MySql::create()
                ->setDbName((string) ($config['database'] ?? ''))
                ->setUserName((string) ($config['username'] ?? ''))
                ->setPassword((string) ($config['password'] ?? ''))
                ->setHost((string) ($config['host'] ?? '127.0.0.1'))
                ->setPort((int) ($config['port'] ?? 3306))
                ->setOutputFile($sqlPath);

            $dumpBinaryPath = $config['dump']['dump_binary_path'] ?? null;

            if (filled($dumpBinaryPath)) {
                $dumper->setDumpBinaryPath((string) $dumpBinaryPath);
            }

            $dumper->dump();

            return;
        }

        if ($connection === 'sqlite') {
            Sqlite::create()
                ->setDbName((string) ($config['database'] ?? ''))
                ->setOutputFile($sqlPath)
                ->dump();

            return;
        }

        throw new RuntimeException("El backup por correo no soporta la conexión {$connection}.");
    }

    private function createEncryptedZip(string $sqlPath, string $zipPath, string $password): void
    {
        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException('No se pudo crear el archivo zip del backup.');
        }

        if (! $zip->addFile($sqlPath, 'database.sql')) {
            $zip->close();
            File::delete($zipPath);

            throw new RuntimeException('No se pudo añadir la base de datos al zip.');
        }

        $encrypted = $zip->setEncryptionName('database.sql', ZipArchive::EM_AES_256, $password);

        $zip->close();

        if (! $encrypted) {
            File::delete($zipPath);

            throw new RuntimeException('No se pudo cifrar el backup con AES-256.');
        }
    }
}
