<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Functional\Frontend;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders a page with Counter elements through the real frontend (sqlite) for both installation
 * paths (static template, site set) and both asset modes (per element, site-wide).
 */
final class CounterRenderingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-counter'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/records.csv');
    }

    /**
     * @param list<string> $extraConstants
     */
    private function setUpStaticTemplateSite(string $locale, array $extraConstants = []): void
    {
        $this->setUpFrontendRootPage(1, [
            'constants' => ['EXT:pxe_counter/Configuration/TypoScript/constants.typoscript'],
            'setup' => [
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                'EXT:pxe_counter/Configuration/TypoScript/setup.typoscript',
                'EXT:pxe_counter/Tests/Functional/Fixtures/Frontend/page.typoscript',
            ],
        ]);
        if ($extraConstants !== []) {
            $this->appendToSysTemplate('constants', implode(LF, $extraConstants));
        }
        $this->writeSiteConfiguration($locale, '');
    }

    private function setUpSiteSetSite(string $locale, string $settingsYaml = ''): void
    {
        $this->setUpFrontendRootPage(1, [
            'setup' => ['EXT:pxe_counter/Tests/Functional/Fixtures/Frontend/page.typoscript'],
        ]);
        // The testing framework creates the root template with "clear" flags, which would reset the
        // TypoScript the site set contributes. A set-based site has no such clearing template.
        $this->getConnectionPool()->getConnectionForTable('sys_template')
            ->update('sys_template', ['clear' => 0], ['pid' => 1]);
        $this->writeSiteConfiguration($locale, "dependencies:\n  - pixelegg/counter\n" . $settingsYaml);
    }

    private function appendToSysTemplate(string $field, string $typoScript): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $row = $connection->select([$field], 'sys_template', ['pid' => 1])->fetchAssociative();
        $connection->update(
            'sys_template',
            [$field => ((string)($row[$field] ?? '')) . LF . $typoScript],
            ['pid' => 1]
        );
    }

    private function writeSiteConfiguration(string $locale, string $extraYaml): void
    {
        // The include tree cache of a set-based site is keyed by site identifier only; a changed
        // site configuration (sets, settings) must not be served from the previous test's cache.
        $this->get(CacheManager::class)->flushCaches();
        $siteDir = Environment::getConfigPath() . '/sites/main';
        GeneralUtility::mkdir_deep($siteDir);
        file_put_contents(
            $siteDir . '/config.yaml',
            "rootPageId: 1\nbase: 'http://localhost/'\n" . $extraYaml
            . "languages:\n  - languageId: 0\n    title: Default\n    locale: " . $locale . "\n    base: /\n"
        );
    }

    private function render(string $uri = 'http://localhost/'): string
    {
        return (string)$this->executeFrontendSubRequest(new InternalRequest($uri))->getBody();
    }

    #[Test]
    public function finalValueIsRenderedLocalisedForTheSiteLanguageWithoutJavaScript(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8');
        $html = $this->render();

        // German grouping/decimal separators, one decimal place, prefix and suffix as own spans
        self::assertStringContainsString('<span class="pxe-counter__value">1.234,5</span>', $html);
        self::assertStringContainsString('<span class="pxe-counter__prefix">€</span>', $html);
        self::assertStringContainsString('<span class="pxe-counter__suffix">%</span>', $html);
        self::assertStringContainsString('<span class="pxe-counter__label">Caption text</span>', $html);
        self::assertStringContainsString('data-locale="de-DE"', $html);
        // nothing is aria-hidden before the script runs: the number is readable as rendered
        self::assertStringNotContainsString('aria-hidden', $html);
        self::assertStringNotContainsString('aria-live', $html);
    }

    #[Test]
    public function numberFormattingFollowsAnEnglishSiteLanguage(): void
    {
        $this->setUpStaticTemplateSite('en_US.UTF-8');
        $html = $this->render();

        self::assertStringContainsString('<span class="pxe-counter__value">1,234.5</span>', $html);
        self::assertStringContainsString('data-locale="en-US"', $html);
    }

    #[Test]
    public function dataAttributesCarryTheAnimationSettings(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8');
        $html = $this->render();

        self::assertStringContainsString('data-from="0"', $html);
        self::assertStringContainsString('data-to="1234.5"', $html);
        self::assertStringContainsString('data-duration="2.5"', $html);
        self::assertStringContainsString('data-delay="0.5"', $html);
        self::assertStringContainsString('data-decimals="1"', $html);
        self::assertStringContainsString('data-separator="1"', $html);
        self::assertStringContainsString('data-easing="linear"', $html);
        self::assertStringContainsString('class="pxe-counter layout-2"', $html);
    }

    #[Test]
    public function unknownEasingAndLayoutFromOldRecordsFallBackToDefaults(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8');
        $html = $this->render();

        self::assertStringContainsString('data-easing="ease-out"', $html);
        self::assertStringContainsString('class="pxe-counter layout-default"', $html);
        self::assertStringContainsString('<span class="pxe-counter__value">42</span>', $html);
    }

    #[Test]
    public function elementRendersThroughFluidStyledContentLayoutWithHeaderAndFrame(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8');
        $html = $this->render();

        self::assertStringContainsString('id="c1"', $html);
        self::assertStringContainsString('frame-type-pxe_counter', $html);
        self::assertMatchesRegularExpression('#<h2[^>]*>\s*Happy clients\s*</h2>#', $html);
        // subheader from the core "headers" palette is rendered as well
        self::assertMatchesRegularExpression('#<h3[^>]*>\s*Since 1995\s*</h3>#', $html);
    }

    #[Test]
    public function assetsAreIncludedOnlyOnPagesWithACounterByDefault(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8');

        $withCounters = $this->render();
        self::assertSame(1, substr_count($withCounters, 'Css/counter.css'), 'stylesheet included once for two elements');
        self::assertSame(1, substr_count($withCounters, 'JavaScript/counter.js'), 'script included once for two elements');
        self::assertMatchesRegularExpression('#<link[^>]+counter\.css[^>]*rel="stylesheet"#', $withCounters);
        self::assertMatchesRegularExpression('#<script[^>]*defer[^>]*counter\.js|<script[^>]*counter\.js[^>]*defer#', $withCounters);

        $withoutCounters = $this->render('http://localhost/empty');
        self::assertStringNotContainsString('counter.css', $withoutCounters);
        self::assertStringNotContainsString('counter.js', $withoutCounters);
    }

    #[Test]
    public function assetsAreIncludedOnEveryPageWhenConstantIsOne(): void
    {
        $this->setUpStaticTemplateSite('de_DE.UTF-8', ['plugin.tx_pxecounter.settings.includeAssetsGlobally = 1']);

        $withCounters = $this->render();
        self::assertSame(1, substr_count($withCounters, 'Css/counter.css'), 'no duplicate from the template');
        self::assertSame(1, substr_count($withCounters, 'JavaScript/counter.js'), 'no duplicate from the template');

        $withoutCounters = $this->render('http://localhost/empty');
        self::assertMatchesRegularExpression('#<link[^>]+counter\.css#', $withoutCounters);
        self::assertMatchesRegularExpression('#<script[^>]*defer[^>]*counter\.js|<script[^>]*counter\.js[^>]*defer#', $withoutCounters);
    }

    #[Test]
    public function decimalValueSavedThroughDataHandlerKeepsItsDecimals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        // Saved like the backend form does: start 0, end 4.5, one decimal place, on the page "Empty".
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tt_content' => [
                'NEW1' => [
                    'pid' => 2,
                    'CType' => 'pxe_counter',
                    'header' => 'Rating',
                    'colPos' => 0,
                    'pi_flexform' => [
                        'data' => [
                            'main' => [
                                'lDEF' => [
                                    'counter_first' => ['vDEF' => '0'],
                                    'counter_last' => ['vDEF' => '4.5'],
                                    'decimals' => ['vDEF' => '1'],
                                    'separator' => ['vDEF' => '1'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);

        $uid = (int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0);
        $flexForm = (string)$this->getConnectionPool()->getConnectionForTable('tt_content')
            ->select(['pi_flexform'], 'tt_content', ['uid' => $uid])->fetchOne();
        self::assertMatchesRegularExpression('#index="counter_last">\s*<value index="vDEF">4\.50</value>#', $flexForm);

        $this->setUpStaticTemplateSite('de_DE.UTF-8');
        $html = $this->render('http://localhost/empty');
        self::assertStringContainsString('<span class="pxe-counter__value">4,5</span>', $html);
        self::assertStringContainsString('data-to="4.5"', $html);
        self::assertStringContainsString('data-decimals="1"', $html);

        $this->writeSiteConfiguration('en_US.UTF-8', '');
        self::assertStringContainsString('<span class="pxe-counter__value">4.5</span>', $this->render('http://localhost/empty'));
    }

    #[Test]
    public function siteSetProvidesTheSameRenderingWithoutStaticTemplate(): void
    {
        $this->setUpSiteSetSite('fr_FR.UTF-8');
        $html = $this->render();

        // French grouping uses a narrow no-break space (U+202F)
        self::assertStringContainsString('<span class="pxe-counter__value">1' . "\u{202F}" . '234,5</span>', $html);
        self::assertStringContainsString('frame-type-pxe_counter', $html);
        self::assertSame(1, substr_count($html, 'Css/counter.css'));
        self::assertStringNotContainsString('counter.css', $this->render('http://localhost/empty'));
    }

    #[Test]
    public function siteSetSettingSwitchesToPageIncludes(): void
    {
        $this->setUpSiteSetSite('de_DE.UTF-8', "settings:\n  plugin:\n    tx_pxecounter:\n      settings:\n        includeAssetsGlobally: true\n");
        $html = $this->render();

        self::assertSame(1, substr_count($html, 'Css/counter.css'));
        self::assertStringContainsString('counter.css', $this->render('http://localhost/empty'));
    }
}
