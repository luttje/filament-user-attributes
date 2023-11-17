<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

use Luttje\FilamentUserAttributes\CodeGeneration\CodeEditor;

it('can edit code and create a safe backup', function () {
    $original = <<<PHP
<?php

namespace Luttje\FilamentUserAttributes\Tests\CodeGeneration;

class TestClass
{
    public function handle()
    {
        return 'original';
    }
}
PHP;

    // Create a temporary file to edit
    $file = tempnam(sys_get_temp_dir(), 'filament-user-attributes');
    file_put_contents($file, $original);

    $editor = CodeEditor::make();
    $edit = $editor->editFileWithBackup($file, function ($code) use ($editor) {
        return $editor->modifyMethod($code, 'handle', function ($method) {
            $method->stmts = [];
            return $method;
        });
    });

    $backupContents = file_get_contents($edit->getBackupFilePath());

    expect($edit->getCode())->toContain('public function handle()');
    expect($original)->toContain('return \'original\'');
    expect($edit->getCode())->not->toContain('return \'original\'');
    expect($backupContents)->toEqual($original);
});
