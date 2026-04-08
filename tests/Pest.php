<?php

use App\Domain\Ticketing\Security\TicketKeyRing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        setUpTicketSigningKey();
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        setUpTicketSigningKey();
    })
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Provision an isolated ticket signing key in a temp directory and switch the
 * `tickets.*` config to use it. Returns the kid.
 */
function setUpTicketSigningKey(?string $kid = null): string
{
    $kid ??= 'test'.bin2hex(random_bytes(4));
    $dir = sys_get_temp_dir().'/lan-ticket-keys-'.bin2hex(random_bytes(4));

    if (! is_dir($dir)) {
        mkdir($dir, 0700, true);
    }

    $keypair = sodium_crypto_sign_keypair();
    file_put_contents($dir.'/'.$kid.'.key', $keypair);
    chmod($dir.'/'.$kid.'.key', 0600);

    config()->set('tickets.pepper', 'test-pepper-'.bin2hex(random_bytes(8)));
    config()->set('tickets.signing.keys_path', $dir);
    config()->set('tickets.signing.active_kid', $kid);
    config()->set('tickets.signing.verify_kids', [$kid]);

    app()->forgetInstance(TicketKeyRing::class);

    return $kid;
}
