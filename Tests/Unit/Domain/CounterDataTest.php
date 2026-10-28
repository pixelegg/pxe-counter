<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pixelegg\PxeCounter\Domain\CounterData;

final class CounterDataTest extends TestCase
{
    #[Test]
    #[DataProvider('incompleteProvider')]
    public function isIncompleteDetectsNonAnimatingCounters(bool $hasValues, float $from, float $to, bool $expected): void
    {
        self::assertSame($expected, CounterData::isIncomplete($hasValues, $from, $to));
    }

    /**
     * @return array<string, array{0:bool,1:float,2:float,3:bool}>
     */
    public static function incompleteProvider(): array
    {
        return [
            'normal range' => [true, 0.0, 1200.0, false],
            'equal from/to' => [true, 500.0, 500.0, true],
            'missing values' => [false, 0.0, 0.0, true],
            'negative range' => [true, -10.0, 0.0, false],
        ];
    }

    #[Test]
    public function isValidEasingAcceptsOnlyKnownEasings(): void
    {
        self::assertTrue(CounterData::isValidEasing('ease-out'));
        self::assertTrue(CounterData::isValidEasing('linear'));
        self::assertTrue(CounterData::isValidEasing('ease-in-out'));
        self::assertFalse(CounterData::isValidEasing('fade-in-section'));
        self::assertFalse(CounterData::isValidEasing(''));
    }

    #[Test]
    public function isValidLayoutAcceptsOnlyKnownLayouts(): void
    {
        self::assertTrue(CounterData::isValidLayout('layout-default'));
        self::assertTrue(CounterData::isValidLayout('layout-3'));
        self::assertFalse(CounterData::isValidLayout('layout-4'));
        self::assertFalse(CounterData::isValidLayout(''));
    }

    #[Test]
    public function clampDecimalsKeepsRangeZeroToFour(): void
    {
        self::assertSame(0, CounterData::clampDecimals(-3));
        self::assertSame(2, CounterData::clampDecimals(2));
        self::assertSame(4, CounterData::clampDecimals(9));
    }

    #[Test]
    #[DataProvider('formatProvider')]
    public function formatNumberFollowsLocale(float $value, int $decimals, bool $grouping, string $locale, string $expected): void
    {
        self::assertSame($expected, CounterData::formatNumber($value, $decimals, $grouping, $locale));
    }

    /**
     * @return array<string, array{0:float,1:int,2:bool,3:string,4:string}>
     */
    public static function formatProvider(): array
    {
        return [
            'de grouping' => [1234567.0, 0, true, 'de_DE', '1.234.567'],
            'de decimals' => [1234.5, 2, true, 'de_DE', '1.234,50'],
            'en grouping' => [1234567.0, 0, true, 'en_US', '1,234,567'],
            'en decimals' => [1234.5, 1, true, 'en', '1,234.5'],
            'no grouping' => [1234567.0, 0, false, 'de_DE', '1234567'],
            'bcp47 tag' => [1234.5, 1, true, 'en-GB', '1,234.5'],
            'rounding' => [0.12346, 4, true, 'en', '0.1235'],
            'decimals clamped' => [1.5, 9, true, 'en', '1.5000'],
            'empty locale falls back to en' => [1234.0, 0, true, '', '1,234'],
        ];
    }

    #[Test]
    #[DataProvider('languageTagProvider')]
    public function toLanguageTagProducesBcp47(string $locale, string $expected): void
    {
        self::assertSame($expected, CounterData::toLanguageTag($locale));
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    public static function languageTagProvider(): array
    {
        return [
            'posix style' => ['de_DE', 'de-DE'],
            'with charset' => ['en_US.UTF-8', 'en-US'],
            'already a tag' => ['fr-FR', 'fr-FR'],
            'language only' => ['de', 'de'],
            'C locale' => ['C', 'en'],
            'empty' => ['', 'en'],
        ];
    }
}
