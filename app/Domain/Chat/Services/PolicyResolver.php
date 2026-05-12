<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Contracts\RoomPolicy;
use App\Domain\Chat\Models\ChatRoom;
use InvalidArgumentException;

/**
 * Resolves the `RoomPolicy` instance bound to a chat room by FQCN. The class
 * stored in `chat_rooms.policy_class` is instantiated lazily — either via the
 * Laravel container (so consumer policies can declare constructor dependencies)
 * or via a registered factory for cases that need extra context.
 */
class PolicyResolver
{
    /**
     * @var array<class-string<RoomPolicy>, callable(ChatRoom): RoomPolicy>
     */
    private array $factories = [];

    /**
     * Register a factory for a policy class. Consumer domains should call this
     * from their service provider when their policy needs more context than the
     * container alone can provide (e.g. resolving the `Competition` model from
     * the room key).
     *
     * @param  class-string<RoomPolicy>  $policyClass
     * @param  callable(ChatRoom): RoomPolicy  $factory
     */
    public function register(string $policyClass, callable $factory): void
    {
        $this->factories[$policyClass] = $factory;
    }

    public function resolve(ChatRoom $room): RoomPolicy
    {
        $class = $room->policy_class;

        if ($class === null || $class === '') {
            throw new InvalidArgumentException(
                "Chat room {$room->id} has no bound policy class.",
            );
        }

        if (isset($this->factories[$class])) {
            return ($this->factories[$class])($room);
        }

        $instance = app($class);

        if (! $instance instanceof RoomPolicy) {
            throw new InvalidArgumentException(
                "Policy class {$class} does not implement RoomPolicy.",
            );
        }

        return $instance;
    }
}
