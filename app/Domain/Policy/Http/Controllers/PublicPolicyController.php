<?php

namespace App\Domain\Policy\Http\Controllers;

use App\Domain\Policy\Models\Policy;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PublicPolicyController extends Controller
{
    public function show(Policy $policy): Response
    {
        abort_if($policy->archived_at !== null, 404);

        $policy->load(['currentVersion', 'type:id,key,label']);

        $version = $policy->currentVersion;
        abort_if($version === null, 404);

        return Inertia::render('policies/Show', [
            'policy' => [
                'key' => $policy->key,
                'name' => $policy->name,
                'description' => $policy->description,
                'type' => $policy->type ? [
                    'key' => $policy->type->key,
                    'label' => $policy->type->label,
                ] : null,
            ],
            'version' => [
                'version_number' => $version->version_number,
                'locale' => $version->locale,
                'content' => $version->content,
                'public_statement' => $version->public_statement,
                'effective_at' => $version->effective_at?->toIso8601String(),
                'published_at' => $version->published_at?->toIso8601String(),
            ],
        ]);
    }
}
