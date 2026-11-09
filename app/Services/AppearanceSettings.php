<?php

namespace App\Services;

use Illuminate\Validation\Rule;

class AppearanceSettings
{
    public static function rules(): array
    {
        return [
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/', function ($attribute, $value, $fail) {
                if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)
                    && self::whiteTextContrast($value) < 4.5) {
                    $fail('Choose a darker primary color for readable white button text (minimum 4.5:1 contrast).');
                }
            }],
            'font_family' => ['required', Rule::in(array_keys(config('appearance.fonts')))],
            'font_size' => ['required', 'integer', Rule::in(config('appearance.sizes'))],
            'density' => ['required', Rule::in(config('appearance.densities'))],
        ];
    }

    public static function whiteTextContrast(string $hex): float
    {
        $channels = [];
        foreach ([1, 3, 5] as $start) {
            $v = hexdec(substr($hex, $start, 2)) / 255;
            $channels[] = $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }

        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

        return 1.05 / ($luminance + 0.05);
    }

    public static function fontStack(string $key): string
    {
        return match ($key) {
            'arial' => 'Arial, Helvetica, sans-serif',
            'verdana' => 'Verdana, Geneva, sans-serif',
            'georgia' => 'Georgia, serif',
            default => 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        };
    }
}
