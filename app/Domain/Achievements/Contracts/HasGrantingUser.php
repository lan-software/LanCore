<?php

namespace App\Domain\Achievements\Contracts;

use App\Models\User;

/**
 * Opt-in marker for events that should be selectable as achievement triggers
 * even when the implementing class does NOT expose a `public User $user`
 * property or a `user()` method matching that convention.
 *
 * Most events qualify via convention — a typed public `user` property is the
 * normal path and requires no interface. Use this contract when the user
 * isn't reachable that way (e.g. the event holds only a `user_id` int).
 *
 * @see docs/mil-std-498/SRS.md ACH-F-006
 */
interface HasGrantingUser
{
    public function grantingUser(): ?User;
}
