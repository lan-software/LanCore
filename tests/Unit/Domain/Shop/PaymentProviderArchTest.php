<?php

use App\Domain\Shop\Contracts\PaymentProvider;

/**
 * Architectural invariant: every concrete class under the PaymentProviders
 * namespace (that is not the manager itself) must implement the
 * PaymentProvider contract. This stops a future PR from accidentally adding
 * a rogue class into the folder without wiring it through the interface.
 */
it('every class in App\\Domain\\Shop\\PaymentProviders except PaymentProviderManager implements PaymentProvider', function () {
    $dir = app_path('Domain/Shop/PaymentProviders');
    $files = glob($dir.'/*.php') ?: [];

    foreach ($files as $file) {
        $basename = basename($file, '.php');

        if ($basename === 'PaymentProviderManager') {
            continue;
        }

        $class = "App\\Domain\\Shop\\PaymentProviders\\{$basename}";
        expect(class_exists($class))->toBeTrue("{$class} should exist");
        expect(is_subclass_of($class, PaymentProvider::class))
            ->toBeTrue("{$class} must implement ".PaymentProvider::class);
    }
});
