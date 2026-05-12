<?php

use App\Domain\Policy\Support\PolicyVersionDiff;

it('marks added lines with the green class and removed lines with the red class', function (): void {
    $from = "line one\nline two\nline three";
    $to = "line one\nline two — edited\nline three\nbrand new line";

    $html = PolicyVersionDiff::render($from, $to);

    expect($html)->toContain('bg-green-50')
        ->and($html)->toContain('bg-red-50')
        ->and($html)->toContain('brand new line')
        ->and($html)->toContain('line two');
});

it('produces only context rows when contents are identical', function (): void {
    $content = "alpha\nbeta\ngamma";

    $html = PolicyVersionDiff::render($content, $content);

    expect($html)->not->toContain('bg-green-50')
        ->and($html)->not->toContain('bg-red-50')
        ->and($html)->toContain('alpha');
});

it('escapes HTML in source content', function (): void {
    $from = '<script>alert(1)</script>';
    $to = '<script>alert(2)</script>';

    $html = PolicyVersionDiff::render($from, $to);

    expect($html)->not->toContain('<script>alert(1)')
        ->and($html)->not->toContain('<script>alert(2)')
        ->and($html)->toContain('&lt;script&gt;');
});

it('rows() returns structured op rows that mirror the rendered diff', function (): void {
    $from = "alpha\nbeta\ngamma";
    $to = "alpha\nbeta — edited\ngamma\ndelta";

    $rows = PolicyVersionDiff::rows($from, $to);

    expect($rows)->toBeArray()->and(count($rows))->toBeGreaterThan(0);

    foreach ($rows as $row) {
        expect($row)->toHaveKeys(['op', 'line'])
            ->and($row['op'])->toBeIn(['eq', 'add', 'del']);
    }

    $ops = array_map(fn (array $row): string => $row['op'], $rows);

    expect($ops)->toContain('add')
        ->and($ops)->toContain('eq');

    $lines = array_map(fn (array $row): string => $row['line'], $rows);

    expect($lines)->toContain('alpha')
        ->and($lines)->toContain('delta')
        ->and($lines)->toContain('beta — edited');
});

it('rows() returns only eq rows when contents are identical', function (): void {
    $content = "one\ntwo";

    $rows = PolicyVersionDiff::rows($content, $content);

    expect($rows)->not->toBeEmpty();

    foreach ($rows as $row) {
        expect($row['op'])->toBe('eq');
    }
});
