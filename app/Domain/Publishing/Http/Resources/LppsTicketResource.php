<?php

namespace App\Domain\Publishing\Http\Resources;

use App\Domain\Shop\Support\CurrencyResolver;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maps a {@see TicketType} to a LAN Party
 * Publishing Standard v2 ticket object.
 *
 * @mixin TicketType
 *
 * @see docs/mil-std-498/SRS.md PUB-F-002
 */
class LppsTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'priceCurrency' => CurrencyResolver::upperCode(),
            'price' => round((float) $this->price / 100, 2),
            'availability' => $this->availabilityUrl(),
            'validFrom' => $this->purchase_from?->format('Y-m-d\TH:i:s'),
            'validThrough' => $this->purchase_until?->format('Y-m-d\TH:i:s'),
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * Map the ticket's sale window and quota to a schema.org ItemAvailability
     * URL, as required by the standard.
     */
    private function availabilityUrl(): string
    {
        $now = now();

        if ($this->purchase_from !== null && $now->isBefore($this->purchase_from)) {
            return 'https://schema.org/PreSale';
        }

        if ($this->purchase_until !== null && $now->isAfter($this->purchase_until)) {
            return 'https://schema.org/OutOfStock';
        }

        if ($this->remainingQuota() <= 0) {
            return 'https://schema.org/SoldOut';
        }

        return 'https://schema.org/InStock';
    }
}
