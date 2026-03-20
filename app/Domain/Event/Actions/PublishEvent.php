<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishEvent
{
    /**
     * Publish an event after validating completeness requirements.
     *
     * @throws ValidationException
     */
    public function execute(Event $event): void
    {
        $errors = [];

        if (empty($event->description)) {
            $errors['description'] = 'A description is required before publishing.';
        }

        if (empty($event->venue_id)) {
            $errors['venue_id'] = 'A venue must be assigned before publishing.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($event): void {
            $event->status = EventStatus::Published;
            $event->save();
        });
    }
}
