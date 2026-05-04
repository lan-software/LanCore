/**
 * Editor-side schema describing which CSS custom properties an admin may
 * override per palette. The shape is mirrored on the backend by
 * `App\Domain\Theme\Support\PaletteVariables` and passed to the editor as
 * an Inertia prop on the Create/Edit pages.
 */
export type PaletteVariable = {
    name: string;
    label: string;
};

export type PaletteVariableGroup = {
    group: string;
    label: string;
    variables: PaletteVariable[];
};

export type PaletteVariablesSchema = PaletteVariableGroup[];
