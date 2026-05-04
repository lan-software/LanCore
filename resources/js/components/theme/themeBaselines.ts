/**
 * Frozen snapshots of the platform's default light and dark CSS-variable
 * tokens (mirroring `:root` and `.dark` in `resources/css/app.css`). Used
 * by the live `ThemePreview` to render each panel in its own isolated
 * cascade — independent of whatever `dark` class the document `<html>`
 * carries when the editor is open.
 *
 * Keep in sync with `resources/css/app.css`. If you adjust the tokens
 * there, mirror the change here so the preview stays accurate.
 */
export const LIGHT_BASELINE: Record<string, string> = {
    '--background': 'hsl(0 0% 100%)',
    '--foreground': 'hsl(0 0% 3.9%)',
    '--card': 'hsl(0 0% 100%)',
    '--card-foreground': 'hsl(0 0% 3.9%)',
    '--popover': 'hsl(0 0% 100%)',
    '--popover-foreground': 'hsl(0 0% 3.9%)',
    '--primary': 'hsl(0 0% 9%)',
    '--primary-foreground': 'hsl(0 0% 98%)',
    '--secondary': 'hsl(0 0% 92.1%)',
    '--secondary-foreground': 'hsl(0 0% 9%)',
    '--muted': 'hsl(0 0% 96.1%)',
    '--muted-foreground': 'hsl(0 0% 45.1%)',
    '--accent': 'hsl(0 0% 96.1%)',
    '--accent-foreground': 'hsl(0 0% 9%)',
    '--destructive': 'hsl(0 84.2% 60.2%)',
    '--destructive-foreground': 'hsl(0 0% 98%)',
    '--border': 'hsl(0 0% 92.8%)',
    '--input': 'hsl(0 0% 89.8%)',
    '--ring': 'hsl(0 0% 3.9%)',
    '--sidebar-background': 'hsl(0 0% 98%)',
    '--sidebar-foreground': 'hsl(240 5.3% 26.1%)',
    '--sidebar-primary': 'hsl(0 0% 10%)',
    '--sidebar-primary-foreground': 'hsl(0 0% 98%)',
    '--sidebar-accent': 'hsl(0 0% 94%)',
    '--sidebar-accent-foreground': 'hsl(0 0% 30%)',
    '--sidebar-border': 'hsl(0 0% 91%)',
    '--sidebar-ring': 'hsl(217.2 91.2% 59.8%)',
    '--sidebar': 'hsl(0 0% 98%)',
};

export const DARK_BASELINE: Record<string, string> = {
    '--background': 'hsl(0 0% 3.9%)',
    '--foreground': 'hsl(0 0% 98%)',
    '--card': 'hsl(0 0% 3.9%)',
    '--card-foreground': 'hsl(0 0% 98%)',
    '--popover': 'hsl(0 0% 3.9%)',
    '--popover-foreground': 'hsl(0 0% 98%)',
    '--primary': 'hsl(0 0% 98%)',
    '--primary-foreground': 'hsl(0 0% 9%)',
    '--secondary': 'hsl(0 0% 14.9%)',
    '--secondary-foreground': 'hsl(0 0% 98%)',
    '--muted': 'hsl(0 0% 16.08%)',
    '--muted-foreground': 'hsl(0 0% 63.9%)',
    '--accent': 'hsl(0 0% 14.9%)',
    '--accent-foreground': 'hsl(0 0% 98%)',
    '--destructive': 'hsl(0 84% 60%)',
    '--destructive-foreground': 'hsl(0 0% 98%)',
    '--border': 'hsl(0 0% 14.9%)',
    '--input': 'hsl(0 0% 14.9%)',
    '--ring': 'hsl(0 0% 83.1%)',
    '--sidebar-background': 'hsl(0 0% 7%)',
    '--sidebar-foreground': 'hsl(0 0% 95.9%)',
    '--sidebar-primary': 'hsl(360, 100%, 100%)',
    '--sidebar-primary-foreground': 'hsl(0 0% 100%)',
    '--sidebar-accent': 'hsl(0 0% 15.9%)',
    '--sidebar-accent-foreground': 'hsl(240 4.8% 95.9%)',
    '--sidebar-border': 'hsl(0 0% 15.9%)',
    '--sidebar-ring': 'hsl(217.2 91.2% 59.8%)',
    '--sidebar': 'hsl(240 5.9% 10%)',
};
