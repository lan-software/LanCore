<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function all($columns = ['*']): \Illuminate\Database\Eloquent\Collection
    {
        return parent::all($columns);
    }

    /**
     * @return array<string, mixed>
     */
    public static function asArray(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /**
     * @return array<string, string|null>
     */
    public static function forInvoice(): array
    {
        $settings = static::asArray();
        $logoPath = $settings['logo'] ?? null;

        return [
            'name' => $settings['name'] ?? config('app.name'),
            'address_line1' => $settings['address_line1'] ?? '',
            'address_line2' => $settings['address_line2'] ?? '',
            'email' => $settings['email'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'website' => $settings['website'] ?? '',
            'tax_id' => $settings['tax_id'] ?? '',
            'registration_id' => $settings['registration_id'] ?? '',
            'legal_notice' => $settings['legal_notice'] ?? '',
            'logo_base64' => $logoPath ? static::logoToBase64($logoPath) : null,
        ];
    }

    private static function logoToBase64(string $path): ?string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);
        $mime = $disk->mimeType($path);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
