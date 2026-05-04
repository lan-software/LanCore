/**
 * Active palette Theme payload exposed via the Inertia `activeTheme` shared prop.
 *
 * Populated by the `ResolveEventTheme` HTTP middleware. The middleware first
 * checks the route-bound `Event` for an assigned theme, then falls back to
 * the organization-wide default theme; null otherwise.
 *
 * `lightConfig` and `darkConfig` are maps of CSS custom properties (e.g.
 * `{"--primary": "#0a246a"}`) applied to `:root` and `.dark` respectively.
 *
 * @see docs/mil-std-498/SRS.md THM-F-005
 */
export type ThemeContext = {
    id: number;
    name: string;
    lightConfig: Record<string, string>;
    darkConfig: Record<string, string>;
    source: 'event' | 'organization';
};
