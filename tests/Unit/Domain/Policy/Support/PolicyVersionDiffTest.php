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
