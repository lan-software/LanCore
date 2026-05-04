<?php

namespace App\Domain\Theme\Support;

/**
 * Curated allowlist of CSS custom properties an admin may override via
 * a Theme palette. The keys mirror the design-system tokens declared in
 * `resources/css/app.css` (and consumed indirectly by the Tailwind v4
 * `@theme inline` block).
 *
 * Grouping powers the layout of the theme editor's color picker columns.
 *
 * @see resources/css/app.css
 * @see docs/mil-std-498/SDD.md §5.11
 */
class PaletteVariables
{
    /**
     * @return array<int, array{group: string, label: string, variables: array<int, array{name: string, label: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'group' => 'surface',
                'label' => 'Surface',
                'variables' => [
                    ['name' => '--background', 'label' => 'Background'],
                    ['name' => '--foreground', 'label' => 'Foreground'],
                    ['name' => '--card', 'label' => 'Card'],
                    ['name' => '--card-foreground', 'label' => 'Card text'],
                    ['name' => '--muted', 'label' => 'Muted'],
                    ['name' => '--muted-foreground', 'label' => 'Muted text'],
                    ['name' => '--border', 'label' => 'Border'],
                    ['name' => '--input', 'label' => 'Input border'],
                ],
            ],
            [
                'group' => 'brand',
                'label' => 'Brand',
                'variables' => [
                    ['name' => '--primary', 'label' => 'Primary'],
                    ['name' => '--primary-foreground', 'label' => 'Primary text'],
                    ['name' => '--secondary', 'label' => 'Secondary'],
                    ['name' => '--secondary-foreground', 'label' => 'Secondary text'],
                    ['name' => '--accent', 'label' => 'Accent'],
                    ['name' => '--accent-foreground', 'label' => 'Accent text'],
                    ['name' => '--ring', 'label' => 'Focus ring'],
                ],
            ],
            [
                'group' => 'sidebar',
                'label' => 'Sidebar',
                'variables' => [
                    ['name' => '--sidebar-background', 'label' => 'Background'],
                    ['name' => '--sidebar-foreground', 'label' => 'Foreground'],
                    ['name' => '--sidebar-primary', 'label' => 'Primary'],
                    ['name' => '--sidebar-primary-foreground', 'label' => 'Primary text'],
                    ['name' => '--sidebar-accent', 'label' => 'Accent'],
                    ['name' => '--sidebar-accent-foreground', 'label' => 'Accent text'],
                    ['name' => '--sidebar-border', 'label' => 'Border'],
                ],
            ],
        ];
    }

    /**
     * Flat list of allowed CSS variable names.
     *
     * @return array<int, string>
     */
    public static function allowedKeys(): array
    {
        $keys = [];

        foreach (self::groups() as $group) {
            foreach ($group['variables'] as $variable) {
                $keys[] = $variable['name'];
            }
        }

        return $keys;
    }

    /**
     * Returns the editor schema (groups + variables) the frontend needs.
     *
     * @return array<int, array{group: string, label: string, variables: array<int, array{name: string, label: string}>}>
     */
    public static function all(): array
    {
        return self::groups();
    }
}
