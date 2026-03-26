<?php

namespace App\Console\Commands;

use App\Domain\Integration\Actions\CreateIntegrationApp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('integration:create {name : The name of the integration app} {--slug= : Custom slug (auto-generated from name if omitted)} {--description= : Description of the integration} {--callback-url= : Callback URL for bootstrap redirects} {--scopes=* : Allowed scopes (user:read, user:email, user:roles)}')]
#[Description('Create a new integration application')]
class CreateIntegrationAppCommand extends Command
{
    public function handle(CreateIntegrationApp $createIntegrationApp): int
    {
        $attributes = [
            'name' => $this->argument('name'),
            'description' => $this->option('description'),
            'callback_url' => $this->option('callback-url'),
            'allowed_scopes' => $this->option('scopes') ?: ['user:read'],
            'is_active' => true,
        ];

        if ($slug = $this->option('slug')) {
            $attributes['slug'] = $slug;
        }

        $app = $createIntegrationApp->execute($attributes);

        $this->info("Integration app '{$app->name}' created successfully.");
        $this->table(['ID', 'Name', 'Slug', 'Scopes'], [
            [$app->id, $app->name, $app->slug, implode(', ', $app->allowed_scopes ?? [])],
        ]);

        return self::SUCCESS;
    }
}
