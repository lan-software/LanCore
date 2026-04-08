<?php

/**
 * Architectural guard: crypto primitives for ticket signing must live only in
 * the Ticketing Security namespace (plus the rotate command that generates keys).
 */
arch('sodium sign primitives are restricted to Ticketing\\Security')
    ->expect(['sodium_crypto_sign_detached', 'sodium_crypto_sign_verify_detached', 'sodium_crypto_sign_keypair'])
    ->toOnlyBeUsedIn([
        'App\\Domain\\Ticketing\\Security',
        'App\\Console\\Commands\\RotateTicketSigningKeyCommand',
    ]);
