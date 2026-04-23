<?php

namespace App\Console\Commands;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketTokenService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-shot deployment helper: rotate every ticket that still holds a
 * pre-deterministic (legacy) nonce hash so the stored hash aligns with
 * HMAC(pepper, ticket_id || epoch).
 *
 * After this command runs, every previously-issued printed QR becomes
 * invalid at the scanner. Users must re-download the PDF or display the
 * live QR from "My Tickets".
 *
 * Safe to re-run — each invocation bumps the rotation epoch by one.
 *
 * @see docs/mil-std-498/SIP.md
 */
#[Signature('tickets:rotate-all {--only-legacy : Only rotate tickets whose rotation_epoch is still 0}')]
#[Description('Rotate the signed QR token for every ticket. Required once on deploy; optional anytime a mass-invalidation is needed.')]
class RotateLegacyTicketTokensCommand extends Command
{
    public function handle(TicketTokenService $service): int
    {
        $query = Ticket::query()->whereNotNull('validation_nonce_hash');

        if ($this->option('only-legacy')) {
            $query->where('validation_rotation_epoch', 0);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No tickets require rotation.');

            return self::SUCCESS;
        }

        $this->info("Rotating {$total} ticket(s)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById(200, function ($tickets) use ($service, $bar): void {
            foreach ($tickets as $ticket) {
                $service->rotate($ticket);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. Rotated {$total} ticket(s).");
        $this->warn('All previously printed QR codes are now invalid. Ask users to re-download the PDF or show the live QR from My Tickets.');

        return self::SUCCESS;
    }
}
