<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tickets:keys:rotate')]
#[Description('Generate a new Ed25519 keypair for ticket signing.')]
class RotateTicketSigningKeyCommand extends Command
{
    public function handle(): int
    {
        $path = (string) config('tickets.signing.keys_path');

        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            $this->error("Unable to create keys directory: {$path}");

            return self::FAILURE;
        }

        $kid = date('Ymd').substr(bin2hex(random_bytes(4)), 0, 8);
        $kid = substr($kid, 0, 16);
        $file = $path.DIRECTORY_SEPARATOR.$kid.'.key';

        if (file_exists($file)) {
            $this->error("Key file already exists: {$file}");

            return self::FAILURE;
        }

        $keypair = sodium_crypto_sign_keypair();
        file_put_contents($file, $keypair);
        chmod($file, 0600);

        $this->info("Generated new ticket signing key: {$kid}");
        $this->line("Key file: {$file}");
        $this->newLine();
        $this->line('Update your environment variables:');
        $this->line("  TICKET_SIGNING_ACTIVE_KID={$kid}");
        $this->line("  TICKET_SIGNING_VERIFY_KIDS={$kid}[,<previous-kids>]");
        $this->newLine();
        $this->warn('This command does NOT modify .env automatically.');

        return self::SUCCESS;
    }
}
