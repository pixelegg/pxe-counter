<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Functional\Controller\Backend;

use PHPUnit\Framework\Attributes\Test;
use Pixelegg\PxeCounter\Controller\Backend\OverviewController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the backend dashboard end-to-end so template/runtime errors
 * (e.g. an invalid ViewHelper argument) fail the build instead of production.
 */
final class OverviewControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $testExtensionsToLoad = ['pixelegg/pxe-counter'];

    private function buildRequest(): ServerRequest
    {
        $route = new Route('/module/pxe-counter', ['packageName' => 'pixelegg/pxe-counter']);
        $request = (new ServerRequest('https://example.com/typo3/module/pxe-counter', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('normalizedParams', \TYPO3\CMS\Core\Http\NormalizedParams::createFromRequest(
                new ServerRequest('https://example.com/typo3/module/pxe-counter', 'GET')
            ));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        return $request;
    }

    private function renderModule(int $backendUserUid = 1): string
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($backendUser);

        $response = $this->get(OverviewController::class)->indexAction($this->buildRequest());
        self::assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    private function setFlexFormValue(int $contentUid, string $field, string $value): void
    {
        // Query builder without restrictions: Connection::select() would skip hidden records.
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $xml = (string)$queryBuilder->select('pi_flexform')->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contentUid, Connection::PARAM_INT)))
            ->executeQuery()->fetchOne();
        $fieldXml = '<field index="' . $field . '"><value index="vDEF">' . $value . '</value></field>';
        $pattern = '#<field index="' . preg_quote($field, '#') . '">.*?</field>#s';
        $xml = preg_match($pattern, $xml) === 1
            ? (string)preg_replace($pattern, $fieldXml, $xml)
            : str_replace('</language>', $fieldXml . '</language>', $xml);
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['pi_flexform' => $xml], ['uid' => $contentUid]);
    }

    #[Test]
    public function indexActionRendersInsideTheCoreModuleLayout(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/counter_content.csv');
        $body = $this->renderModule();

        // Layouts/Module of EXT:backend: docheader and padded module body (TYPO3 13.4 and 14).
        self::assertMatchesRegularExpression('#<div class="module[ "]#', $body);
        self::assertStringContainsString('module-docheader', $body);
        self::assertMatchesRegularExpression('#<div class="module-body t3js-module-body">.*<h1>Counter</h1>#s', $body);
        self::assertMatchesRegularExpression('#<div class="module-docheader.*<div class="module-body#s', $body);
    }

    #[Test]
    public function indexActionRendersDashboardWithUsages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/counter_content.csv');
        $body = $this->renderModule();

        // Table with all three counters, the from→to range and the health badges rendered without errors.
        self::assertStringContainsString('Happy clients', $body);
        self::assertStringContainsString('1,200', $body); // uid 10 counter_last formatted
        self::assertStringContainsString('Won&#039;t animate (start = end)', $body); // uid 11
        self::assertSame(3, substr_count($body, '%5Btt_content%5D'), 'one edit link per counter');
        self::assertStringContainsString('pxe_counter · <a href="https://www.pixelegg.de" target="_blank" rel="noopener"', $body);
        self::assertStringNotContainsString('pxecnt', $body);
        // plural forms for three counters on two pages
        self::assertStringContainsString('>3 Counters</span>', $body);
        self::assertStringContainsString('>2 Pages</span>', $body);
    }

    #[Test]
    public function statisticsUseTheSingularForOneCounterOnOnePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/counter_content.csv');
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['deleted' => 1], ['pid' => 2]);
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->update('tt_content', ['deleted' => 1], ['uid' => 11]);
        $body = $this->renderModule();

        self::assertStringContainsString('>1 Counter</span>', $body);
        self::assertStringContainsString('>1 Page</span>', $body);
    }

    #[Test]
    public function numbersAreFormattedForTheBackendUserLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/counter_content.csv');
        // uid 12: 0 → 4.5 with one decimal place
        $this->setFlexFormValue(12, 'counter_last', '4.5');
        $this->setFlexFormValue(12, 'decimals', '1');

        $english = $this->renderModule(1);
        self::assertStringContainsString('1,200', $english);
        self::assertStringContainsString('0.0 &rarr; 4.5', $english);

        $german = $this->renderModule(2);
        self::assertStringContainsString('0 &rarr; 1.200', $german);
        self::assertStringContainsString('0,0 &rarr; 4,5', $german);
        self::assertStringContainsString('>3 Zähler</span>', $german);
        self::assertStringContainsString('>2 Seiten</span>', $german);
    }

    #[Test]
    public function indexActionRendersEmptyStateWithoutCounters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $body = $this->renderModule();
        self::assertStringContainsString('callout-info', $body);
        self::assertStringContainsString('No Counter elements yet', $body);
        self::assertStringNotContainsString('<table', $body);
    }
}
