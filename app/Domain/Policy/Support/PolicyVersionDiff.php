<?php

namespace App\Domain\Policy\Support;

/**
 * Renders a line-by-line HTML diff between two markdown content blobs.
 *
 * The output is a sequence of `<div class="...">` rows tagged for
 * Tailwind styling on the frontend (added / removed / context). The
 * diff is computed via the standard longest-common-subsequence
 * algorithm — no external dependency.
 */
final class PolicyVersionDiff
{
    public static function render(string $from, string $to): string
    {
        $a = preg_split("/\r\n|\n|\r/", $from) ?: [];
        $b = preg_split("/\r\n|\n|\r/", $to) ?: [];

        $ops = self::diff($a, $b);

        $rows = [];
        foreach ($ops as [$op, $line]) {
            $rows[] = self::renderRow($op, $line);
        }

        return implode("\n", $rows);
    }

    /**
     * Hunt-McIlroy LCS-based diff producing a flat op stream.
     *
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<array{0:string,1:string}>
     */
    private static function diff(array $a, array $b): array
    {
        $m = count($a);
        $n = count($b);

        $lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }

        $ops = [];
        $i = $m;
        $j = $n;

        while ($i > 0 && $j > 0) {
            if ($a[$i - 1] === $b[$j - 1]) {
                array_unshift($ops, ['eq', $a[$i - 1]]);
                $i--;
                $j--;
            } elseif ($lcs[$i - 1][$j] >= $lcs[$i][$j - 1]) {
                array_unshift($ops, ['del', $a[$i - 1]]);
                $i--;
            } else {
                array_unshift($ops, ['add', $b[$j - 1]]);
                $j--;
            }
        }

        while ($i > 0) {
            array_unshift($ops, ['del', $a[$i - 1]]);
            $i--;
        }

        while ($j > 0) {
            array_unshift($ops, ['add', $b[$j - 1]]);
            $j--;
        }

        return $ops;
    }

    private static function renderRow(string $op, string $line): string
    {
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return match ($op) {
            'add' => '<div class="bg-green-50 px-2 py-0.5 text-green-900 dark:bg-green-950/30 dark:text-green-200">+ '.$escaped.'</div>',
            'del' => '<div class="bg-red-50 px-2 py-0.5 text-red-900 line-through dark:bg-red-950/30 dark:text-red-200">- '.$escaped.'</div>',
            default => '<div class="px-2 py-0.5 text-muted-foreground">  '.$escaped.'</div>',
        };
    }
}
