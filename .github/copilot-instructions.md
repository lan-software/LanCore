# LanCore — Additional Notes for GitHub Copilot

This file contains project-specific notes and common pitfalls discovered during development. It supplements the skills in `.github/skills/` (which are managed and overridden by `laravel/boost`) with knowledge that is unique to this codebase.

## Common Pitfalls

### Checkbox / Switch in Inertia `<Form>` Component

shadcn-vue's `<Switch>` and `<Checkbox>` (reka-ui) both send the string `"on"` as their form value, ignoring the `:value` prop. This affects how they can be used inside Inertia's `<Form>` component.

**Boolean fields**: Use `<Checkbox>` (not `<Switch>`) with `prepareForValidation()` to convert `"on"` to a boolean:

```vue
<Checkbox id="is_active" name="is_active" :default-value="true" />
```

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'is_active' => $this->boolean('is_active'),
    ]);
}
```

**Checkbox arrays** (e.g. scopes): Do **NOT** use shadcn-vue `<Checkbox>` — it sends `"on"` for every checked item instead of the scope value. Use native `<input type="checkbox">` so the `:value` attribute is properly included in FormData:

```vue
<input
    type="checkbox"
    :id="`scope-${scope.value}`"
    name="allowed_scopes[]"
    :value="scope.value"
    :checked="model.allowed_scopes?.includes(scope.value)"
    class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
/>
```

Also default to an empty array in `prepareForValidation`:

```php
$this->merge([
    'allowed_scopes' => $this->input('allowed_scopes', []),
]);
```

### Wayfinder `--with-form` Flag

When running `artisan wayfinder:generate` manually, always pass the `--with-form` flag. The Vite plugin has `formVariants: true`, but the artisan command requires the flag explicitly to generate `.form()` method variants.

```bash
vendor/bin/sail artisan wayfinder:generate --with-form
```

### Wayfinder Multi-Parameter Routes

Wayfinder-generated functions for routes with **two or more** parameters accept a single `args` object (or array), **not** separate positional arguments. Single-parameter routes allow a plain number shorthand, but multi-parameter routes do not.

```ts
// WRONG — two positional args, second arg becomes `options`
destroyToken(integrationApp.id, tokenId)

// CORRECT — single object with named parameters
destroyToken({ integration: integrationApp.id, token: tokenId })
```

### Eloquent `updateQuietly()` Ignores Non-Fillable Columns

`updateQuietly()` silently skips columns not listed in the model's `#[Fillable]` attribute. If you need to update a non-fillable column (e.g. `last_used_at`), use `forceFill()->saveQuietly()` instead:

```php
// Will NOT update last_used_at if it's not in #[Fillable]
$model->updateQuietly(['last_used_at' => now()]);

// Will update any column
$model->forceFill(['last_used_at' => now()])->saveQuietly();
```
