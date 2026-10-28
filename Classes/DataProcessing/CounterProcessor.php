<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\DataProcessing;

use Pixelegg\PxeCounter\Domain\CounterData;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Turns the raw FlexForm values of a Counter element (as provided by the FlexFormProcessor
 * under "flexform") into a normalised array and formats start/end values for the site
 * language, so the rendered HTML already contains the final, localised number.
 *
 * Output (default variable name "counter"):
 *   from, to (float) · fromFormatted, toFormatted (string) · decimals (int) · grouping (bool)
 *   duration, delay (float, seconds) · easing, layout (validated) · prefix, suffix, label (string)
 *   locale (BCP 47 tag for the browser) · animate (bool: false when start = end or duration = 0)
 */
final class CounterProcessor implements DataProcessorInterface
{
    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $flexform = is_array($processedData['flexform'] ?? null) ? $processedData['flexform'] : [];
        $locale = $this->resolveLocale($cObj);

        $from = (float)($flexform['counter_first'] ?? 0);
        $to = (float)($flexform['counter_last'] ?? 0);
        $decimals = CounterData::clampDecimals((int)($flexform['decimals'] ?? 0));
        $grouping = (bool)($flexform['separator'] ?? true);
        $duration = max(0.0, (float)($flexform['duration'] ?? 2));
        $delay = max(0.0, (float)($flexform['delay'] ?? 0));
        $easing = (string)($flexform['easing'] ?? CounterData::DEFAULT_EASING);
        $layout = (string)($flexform['layout'] ?? CounterData::DEFAULT_LAYOUT);

        $counter = [
            'from' => $from,
            'to' => $to,
            'fromFormatted' => CounterData::formatNumber($from, $decimals, $grouping, $locale),
            'toFormatted' => CounterData::formatNumber($to, $decimals, $grouping, $locale),
            'decimals' => $decimals,
            'grouping' => $grouping,
            'duration' => $duration,
            'delay' => $delay,
            'easing' => CounterData::isValidEasing($easing) ? $easing : CounterData::DEFAULT_EASING,
            'layout' => CounterData::isValidLayout($layout) ? $layout : CounterData::DEFAULT_LAYOUT,
            'prefix' => trim((string)($flexform['prefix'] ?? '')),
            'suffix' => trim((string)($flexform['suffix'] ?? '')),
            'label' => trim((string)($flexform['label'] ?? '')),
            'locale' => CounterData::toLanguageTag($locale),
            'animate' => $from !== $to && $duration > 0,
        ];

        $as = (string)$cObj->stdWrapValue('as', $processorConfiguration, 'counter');
        $processedData[$as] = $counter;

        return $processedData;
    }

    private function resolveLocale(ContentObjectRenderer $cObj): string
    {
        $language = $cObj->getRequest()->getAttribute('language');
        if (!$language instanceof SiteLanguage) {
            return 'en';
        }

        return $language->getLocale()->getName();
    }
}
