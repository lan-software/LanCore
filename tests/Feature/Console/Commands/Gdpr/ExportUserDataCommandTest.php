<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Role::query()->updateOrCreate(
        ['name' => RoleName::User->value],
        ['label' => 'User'],
    );

    $this->outputDir = storage_path('app/gdpr-exports/test-'.Str::uuid()->toString());
    File::ensureDirectoryExists($this->outputDir);
});

afterEach(function (): void {
    if (isset($this->outputDir) && is_dir($this->outputDir)) {
        File::deleteDirectory($this->outputDir);
    }
});

it('produces a ZIP with the expected sources and manifest', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create([
        'email' => 'export@example.com',
    ]);

    $this->artisan('gdpr:export-user', [
        'email' => 'export@example.com',
        '--output-dir' => $this->outputDir,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $files = glob($this->outputDir.'/'.$user->id.'-*.zip');
    expect($files)->not->toBeEmpty();

    $zipPath = $files[0];

    $zip = new ZipArchive;
    expect($zip->open($zipPath))->toBeTrue();

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($names)->toContain('manifest.json')
        ->and($names)->toContain('README.txt')
        ->and($names)->toContain('profile.json')
        ->and($names)->toContain('policy_acceptances.json');
});

it('encrypts ZIP entries when --password is provided', function (): void {
    User::factory()->withRole(RoleName::User)->create([
        'email' => 'encrypted@example.com',
    ]);

    $this->artisan('gdpr:export-user', [
        'email' => 'encrypted@example.com',
        '--password' => 'correct-horse-battery-staple',
        '--output-dir' => $this->outputDir,
        '--no-interaction' => true,
    ])->assertSuccessful();

    $files = glob($this->outputDir.'/*.zip');
    expect($files)->not->toBeEmpty();

    $zip = new ZipArchive;
    expect($zip->open($files[0]))->toBeTrue();

    $stat = $zip->statIndex(0);
    expect($stat)->not->toBeFalse();
    expect($stat['encryption_method'])->not->toBe(0);
    $zip->close();
});

it('returns failure when the email does not match a user', function (): void {
    $this->artisan('gdpr:export-user', [
        'email' => 'nobody@example.com',
        '--output-dir' => $this->outputDir,
        '--no-interaction' => true,
    ])->assertFailed();
});
