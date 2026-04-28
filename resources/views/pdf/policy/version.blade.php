<!DOCTYPE html>
<html lang="{{ $version->locale }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $policy->name }} — v{{ $version->version_number }}</title>
    <style>
        @page { margin: 24mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; line-height: 1.5; color: #111; }
        h1 { font-size: 22pt; margin-bottom: 4mm; }
        h2 { font-size: 14pt; margin-top: 6mm; }
        .meta { color: #555; font-size: 9pt; margin-bottom: 8mm; }
        .public-statement { background: #f4f4f8; border-left: 4px solid #5b6cff; padding: 4mm 6mm; margin: 6mm 0; }
        .content { margin-top: 6mm; }
        hr { border: none; border-top: 1px solid #ddd; margin: 6mm 0; }
        code { font-family: DejaVu Sans Mono, monospace; }
    </style>
</head>
<body>
    <h1>{{ $policy->name }}</h1>
    <div class="meta">
        Version {{ $version->version_number }} ({{ $version->locale }})
        — published {{ $version->published_at->format('Y-m-d H:i') }} UTC
        — effective {{ $version->effective_at->format('Y-m-d') }}
        @if ($version->is_non_editorial_change)
            — non-editorial change
        @endif
    </div>

    @if ($version->is_non_editorial_change && $version->public_statement)
        <div class="public-statement">
            <strong>Statement from the operator:</strong><br>
            {{ $version->public_statement }}
        </div>
    @endif

    <hr>

    <div class="content">
        {!! \Illuminate\Support\Str::markdown($version->content) !!}
    </div>
</body>
</html>
