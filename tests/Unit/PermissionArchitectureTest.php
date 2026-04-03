<?php

use App\Enums\RolePermissionMap;

it('registers every permission enum case in RolePermissionMap::all()', function () {
    $registeredValues = array_map(
        fn ($p) => $p->value,
        RolePermissionMap::all(),
    );

    $allEnumFiles = [
        ...glob(app_path('Domain/*/Enums/Permission.php')),
        app_path('Enums/Permission.php'),
        app_path('Enums/AuditPermission.php'),
    ];

    foreach ($allEnumFiles as $file) {
        if (! file_exists($file)) {
            continue;
        }

        $relativePath = str_replace(app_path().'/', '', $file);
        $className = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);

        if (! enum_exists($className)) {
            continue;
        }

        foreach ($className::cases() as $case) {
            expect(in_array($case->value, $registeredValues, true))
                ->toBeTrue("{$className}::{$case->name} ({$case->value}) is not registered in RolePermissionMap::all()");
        }
    }
});

it('has no duplicate permission values across all enums', function () {
    $allValues = array_map(
        fn ($p) => $p->value,
        RolePermissionMap::all(),
    );

    $duplicates = array_diff_assoc($allValues, array_unique($allValues));

    expect($duplicates)->toBeEmpty();
});
