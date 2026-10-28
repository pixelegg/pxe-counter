<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Domain;

/**
 * Pure (TYPO3-free) helpers for interpreting and formatting counter values.
 * Kept free of framework calls so it can be unit-tested in isolation.
 */
final class CounterData
{
    /** Easing values the frontend script understands. */
    public const VALID_EASINGS = ['ease-out', 'linear', 'ease-in-out'];

    /** Layout variants (CSS class hooks on the element). */
    public const VALID_LAYOUTS = ['layout-default', 'layout-1', 'layout-2', 'layout-3'];

    public const DEFAULT_EASING = 'ease-out';
    public const DEFAULT_LAYOUT = 'layout-default';
    public const MAX_DECIMALS = 4;

    /**
     * A counter is "incomplete" (won't visibly animate) when its values are
     * missing or the start equals the end.
     */
    public static function isIncomplete(bool $hasValues, float $from, float $to): bool
    {
        return !$hasValues || $from === $to;
    }

    public static function isValidEasing(string $easing): bool
    {
        return in_array($easing, self::VALID_EASINGS, true);
    }

    public static function isValidLayout(string $layout): bool
    {
        return in_array($layout, self::VALID_LAYOUTS, true);
    }

    public static function clampDecimals(int $decimals): int
    {
        return max(0, min(self::MAX_DECIMALS, $decimals));
    }

    /**
     * Locale-aware number formatting (grouping and decimal separators of the given locale),
     * e.g. 1234.5 => "1.234,5" for de_DE and "1,234.5" for en_US.
     *
     * @param string $locale ICU locale such as "de_DE", "de-DE" or "en"
     */
    public static function formatNumber(float $value, int $decimals, bool $grouping, string $locale): string
    {
        $decimals = self::clampDecimals($decimals);
        $formatter = new \NumberFormatter($locale !== '' ? $locale : 'en', \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, $grouping ? 1 : 0);
        $formatted = $formatter->format($value);

        return $formatted === false ? number_format($value, $decimals, '.', $grouping ? ',' : '') : $formatted;
    }

    /**
     * BCP 47 language tag for Intl.NumberFormat in the browser ("de_DE" => "de-DE").
     */
    public static function toLanguageTag(string $locale): string
    {
        $tag = str_replace('_', '-', explode('.', $locale, 2)[0]);

        return $tag === '' || $tag === 'C' || $tag === 'POSIX' ? 'en' : $tag;
    }
}
