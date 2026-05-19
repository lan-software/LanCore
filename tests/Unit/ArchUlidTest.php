<?php

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Symfony\Component\Finder\Finder;

it('uses HasUlids on every domain model', function (): void {
    $finder = (new Finder)
        ->in(app_path('Models'))
        ->in(app_path('Domain'))
        ->files()
        ->name('*.php')
        ->path('Models');

    $violations = [];

    foreach ($finder as $file) {
        $contents = $file->getContents();

        if (! preg_match('/^namespace ([^;]+);/m', $contents, $nsMatch)) {
            continue;
        }

        $class = $nsMatch[1].'\\'.$file->getBasename('.php');

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->isSubclassOf(Model::class)) {
            continue;
        }

        // Rich-pivot models (extending Pivot) keep auto-incrementing int PKs.
        if ($reflection->isSubclassOf(Pivot::class)) {
            continue;
        }

        $traits = collect($reflection->getTraitNames())
            ->merge(array_map(
                fn ($t) => $t->getName(),
                $reflection->getTraits(),
            ));

        $usesHasUlids = collect($reflection->getTraitNames())
            ->concat(class_uses_recursive($class))
            ->contains(HasUlids::class);

        if (! $usesHasUlids) {
            $violations[] = $class;
        }
    }

    expect($violations)->toBe([], 'Models missing HasUlids: '.implode(', ', $violations));
});
