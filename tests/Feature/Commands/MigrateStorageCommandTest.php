<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Storage::fake('s3');
    Storage::fake('s3_public');
    Storage::fake('s3_private');
});

it('migrates all files from local to s3', function () {
    Storage::disk('local')->put('images/photo.jpg', 'image-content');
    Storage::disk('local')->put('docs/readme.txt', 'readme-content');

    $this->artisan('storage:migrate --from=local --to=s3')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists('images/photo.jpg');
    Storage::disk('s3')->assertExists('docs/readme.txt');
});

it('migrates all files from s3 to local', function () {
    Storage::disk('s3')->put('uploads/file.pdf', 'pdf-content');

    $this->artisan('storage:migrate --from=s3 --to=local')
        ->assertSuccessful();

    Storage::disk('local')->assertExists('uploads/file.pdf');
});

it('preserves file contents during migration', function () {
    $content = 'exact file content to preserve';
    Storage::disk('local')->put('test.txt', $content);

    $this->artisan('storage:migrate --from=local --to=s3')
        ->assertSuccessful();

    expect(Storage::disk('s3')->get('test.txt'))->toBe($content);
});

it('succeeds with no files on source disk', function () {
    $this->artisan('storage:migrate --from=local --to=s3')
        ->expectsOutputToContain('No files found on source disk')
        ->assertSuccessful();
});

it('deletes source files after migration when --delete is passed', function () {
    Storage::disk('local')->put('archive/old.txt', 'old content');

    $this->artisan('storage:migrate --from=local --to=s3 --delete')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists('archive/old.txt');
    Storage::disk('local')->assertMissing('archive/old.txt');
});

it('does not delete source files when --delete is not passed', function () {
    Storage::disk('local')->put('keep/file.txt', 'keep this');

    $this->artisan('storage:migrate --from=local --to=s3')
        ->assertSuccessful();

    Storage::disk('local')->assertExists('keep/file.txt');
});

it('only migrates files under the given --path prefix', function () {
    Storage::disk('local')->put('images/photo.jpg', 'photo');
    Storage::disk('local')->put('docs/readme.txt', 'readme');

    $this->artisan('storage:migrate --from=local --to=s3 --path=images')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists('images/photo.jpg');
    Storage::disk('s3')->assertMissing('docs/readme.txt');
});

it('lists files but does not migrate them in dry-run mode', function () {
    Storage::disk('local')->put('sample.txt', 'data');

    $this->artisan('storage:migrate --from=local --to=s3 --dry-run')
        ->expectsOutputToContain('dry-run mode')
        ->expectsOutputToContain('sample.txt')
        ->assertSuccessful();

    Storage::disk('s3')->assertMissing('sample.txt');
});

it('does not delete source files in dry-run mode even with --delete', function () {
    Storage::disk('local')->put('important.txt', 'very important');

    $this->artisan('storage:migrate --from=local --to=s3 --dry-run --delete')
        ->assertSuccessful();

    Storage::disk('local')->assertExists('important.txt');
    Storage::disk('s3')->assertMissing('important.txt');
});

it('fails when source and destination disk are the same', function () {
    $this->artisan('storage:migrate --from=local --to=local')
        ->expectsOutputToContain('Source and destination disks must be different')
        ->assertFailed();
});

it('fails when source disk is invalid', function () {
    $this->artisan('storage:migrate --from=ftp --to=s3')
        ->expectsOutputToContain("Invalid source disk 'ftp'")
        ->assertFailed();
});

it('fails when destination disk is invalid', function () {
    $this->artisan('storage:migrate --from=local --to=ftp')
        ->expectsOutputToContain("Invalid destination disk 'ftp'")
        ->assertFailed();
});

it('migrates files from local to s3_private', function () {
    Storage::disk('local')->put('invoices/1.pdf', 'invoice-content');

    $this->artisan('storage:migrate --from=local --to=s3_private')
        ->assertSuccessful();

    Storage::disk('s3_private')->assertExists('invoices/1.pdf');
});

it('migrates files from public to s3_public', function () {
    Storage::disk('public')->put('organization/logo.png', 'logo-bytes');

    $this->artisan('storage:migrate --from=public --to=s3_public')
        ->assertSuccessful();

    Storage::disk('s3_public')->assertExists('organization/logo.png');
});
