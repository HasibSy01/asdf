<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    /**
     * Signature perintah backup basis data SPPG (OR-002).
     */
    protected $signature = 'mbg:backup {--retention= : Masa simpan cadangan dalam hari}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Membuat salinan cadangan (backup) basis data MBG NutriView secara otomatis dan membersihkan arsip lama.';

    /**
     * Jalankan pencadangan basis data SQLite/MySQL dan pembersihan retensi.
     */
    public function handle(): int
    {
        $connection = config('database.default');
        $connConfig = config("database.connections.{$connection}", []);
        $backupDir = storage_path('app/' . config('mbg.backup.directory', 'backups'));
        $retentionDays = (int) ($this->option('retention') ?: config('mbg.backup.retention_days', 14));

        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Ymd-His');
        $targetFile = null;

        if (($connConfig['driver'] ?? '') === 'sqlite') {
            $source = (string) ($connConfig['database'] ?? '');
            if ($source === ':memory:' || !is_file($source)) {
                $this->error('Berkas basis data SQLite tidak ditemukan atau berjalan di memori (:memory:).');
                return self::FAILURE;
            }

            $targetFile = $backupDir . DIRECTORY_SEPARATOR . "mbg-backup-{$timestamp}.sqlite";
            if (!File::copy($source, $targetFile)) {
                $this->error('Gagal menyalin berkas basis data SQLite.');
                return self::FAILURE;
            }
        } elseif (in_array($connConfig['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            $targetFile = $backupDir . DIRECTORY_SEPARATOR . "mbg-backup-{$timestamp}.sql";
            $configuredDump = (string) config('mbg.backup.mysqldump_bin', 'mysqldump');
            $dumpBin = is_file($configuredDump) ? $configuredDump : 'mysqldump';

            $args = [
                $dumpBin,
                '--single-transaction',
                '--quick',
                '--host=' . ($connConfig['host'] ?? '127.0.0.1'),
                '--port=' . ($connConfig['port'] ?? '3306'),
                '--user=' . ($connConfig['username'] ?? 'root'),
                '--result-file=' . $targetFile,
                $connConfig['database'] ?? 'mbg_nutriview',
            ];

            $env = [];
            if (!empty($connConfig['password'])) {
                $env['MYSQL_PWD'] = $connConfig['password'];
            }

            $process = new Process($args, null, $env);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful() || !is_file($targetFile)) {
                $this->error('Proses mysqldump gagal: ' . trim($process->getErrorOutput()));
                return self::FAILURE;
            }
        } else {
            $this->error("Driver basis data '{$connection}' belum didukung oleh mbg:backup.");
            return self::FAILURE;
        }

        // Bersihkan cadangan yang melampaui masa retensi
        $deleted = 0;
        if ($retentionDays > 0) {
            $cutoff = now()->subDays($retentionDays)->getTimestamp();
            foreach (File::files($backupDir) as $file) {
                if ($file->getMTime() < $cutoff && $file->getRealPath() !== realpath($targetFile)) {
                    File::delete($file->getRealPath());
                    $deleted++;
                }
            }
        }

        $this->info("Cadangan berhasil disimpan: {$targetFile}");
        if ($deleted > 0) {
            $this->line("Arsip lama dihapus ({$deleted} berkas melebihi retensi {$retentionDays} hari).");
        }

        return self::SUCCESS;
    }
}

