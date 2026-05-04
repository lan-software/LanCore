<?php

/**
 * Architectural guard: the curated palette variable allowlist is the single
 * source of truth for which CSS custom properties an admin may override
 * via a Theme. Keep it inside the Theme domain plus the validation rule
 * that consumes it (and tests).
 */
arch('PaletteVariables is restricted to the Theme domain')
    ->expect('App\\Domain\\Theme\\Support\\PaletteVariables')
    ->toOnlyBeUsedIn([
        'App\\Domain\\Theme',
    ]);
