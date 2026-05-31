<?php

namespace App\Domain\Publishing\Actions;

use App\Domain\Publishing\Http\Resources\LppsVenueResource;
use App\Domain\Venue\Models\Venue;
use App\Models\OrganizationSetting;
use App\Support\StorageRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Builds the LAN Party Publishing Standard v2 document just-in-time:
 * a single organisation containing the venues that host published events,
 * each nesting its published events and their visible ticket types.
 *
 * Published events without a venue cannot be nested under the standard's
 * organisation → venues → events hierarchy and are therefore omitted.
 *
 * @see docs/mil-std-498/SSS.md CAP-PUB-001, CAP-PUB-002
 * @see docs/mil-std-498/SRS.md PUB-F-002
 */
class BuildLanPartyDocument
{
    public const SCHEMA_URL = 'https://raw.githubusercontent.com/jamesread/lan-party-publishing-standard/main/lan-party-publishing-standard-v2.schema';

    public const GENERATOR = 'LanCore';

    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $settings = OrganizationSetting::asArray();

        $venues = Venue::query()
            ->whereHas('events', fn (Builder $query) => $query->published())
            ->with([
                'address',
                'events' => fn ($query) => $query->published()
                    ->orderBy('start_date')
                    ->with(['ticketTypes' => fn ($ticket) => $ticket->where('is_hidden', false)->orderBy('name')]),
            ])
            ->orderBy('name')
            ->get();

        return [
            '$schema' => self::SCHEMA_URL,
            'generator' => self::GENERATOR,
            'organisation' => array_filter([
                'apiVersion' => 2,
                'apiType' => 'Organisation',
                'publisherUniqueId' => $this->publisherUniqueId($settings),
                'name' => $settings['name'] ?? config('app.name'),
                'websiteUrl' => $settings['website'] ?? null,
                'steamGroupUrl' => $settings['steam_group_url'] ?? null,
                'discordInviteUrl' => $settings['discord_invite_url'] ?? null,
                'image' => isset($settings['logo']) ? StorageRole::public()->url($settings['logo']) : null,
                'description' => $settings['description'] ?? null,
                'venues' => LppsVenueResource::collection($venues)->resolve(),
            ], fn (mixed $value): bool => $value !== null),
        ];
    }

    /**
     * The publisher's stable unique identifier. Falls back to a slug of the
     * organisation name (or the app name) so the required field is never empty.
     *
     * @param  array<string, mixed>  $settings
     */
    private function publisherUniqueId(array $settings): string
    {
        $configured = $settings['publisher_unique_id'] ?? null;

        if (is_string($configured) && trim($configured) !== '') {
            return $configured;
        }

        $name = (string) ($settings['name'] ?? config('app.name'));

        return Str::slug($name) ?: 'lancore';
    }
}
