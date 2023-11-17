<?php

namespace Luttje\FilamentUserAttributes\CodeGeneration;

/**
 * @internal
 *
 * Represents a code editing transaction. Will return the contents of a modified file.
 * Once the transaction is complete, the file will be written to disk.
 */
class CodeEditTransaction
{
    private $backupPath;

    private $timestamp;

    private $path;

    private $code;

    public function __construct(string $path)
    {
        $this->timestamp = time();
        $this->path = $path;
        $this->code = file_get_contents($path);

        $this->writeBackup($this->code);
    }

    private function writeBackup($originalCode)
    {
        $path = basename($this->path);
        $this->backupPath = storage_path('filament-user-attributes/backups/' . $this->timestamp . '/' . $path);
        $backupDir = dirname($this->backupPath);

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        file_put_contents($this->backupPath, $originalCode);
    }

    public function getBackupFilePath()
    {
        return $this->backupPath;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function edit(\Closure $callback)
    {
        $this->code = $callback($this->code);
        $this->commit();

        return $this->code;
    }

    private function commit()
    {
        $code = $this->code;
        file_put_contents($this->path, $code);
    }
}
