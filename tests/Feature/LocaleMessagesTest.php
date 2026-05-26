<?php

// Prevents vue-i18n linked-message crashes: a literal "@" parses as @:key and
// THROWS in the production vue-i18n runtime, blanking any page that renders the
// message. Literal "@" must be escaped as {'@'}. Locale files are Weblate-managed.

function localeMessageOffenders(array $data, string $prefix = ''): array
{
    $offenders = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
        if (is_array($value)) {
            $offenders = array_merge($offenders, localeMessageOffenders($value, $path));
        } elseif (is_string($value) && str_contains(str_replace("{'@'}", '', $value), '@')) {
            $offenders[] = "{$path} => {$value}";
        }
    }

    return $offenders;
}

it('has no unescaped @ in vue-i18n locale messages', function () {
    $dir = resource_path('js/locales');
    expect(is_dir($dir))->toBeTrue("locale dir missing: {$dir}");

    $offenders = [];
    foreach (glob("{$dir}/*.json") as $file) {
        $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        foreach (localeMessageOffenders($data, basename($file)) as $o) {
            $offenders[] = $o;
        }
    }

    expect($offenders)->toBe([]);
});
