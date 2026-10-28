<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Tests\Functional\Controller\Backend;

use PHPUnit\Framework\Attributes\Test;
use Pixelegg\PxeCounter\Controller\Backend\OverviewController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
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

    #[Test]
    public function indexActionRendersDashboardWithUsages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/counter_content.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($backendUser);

        $controller = $this->get(OverviewController::class);
        $response = $controller->indexAction($this->buildRequest());

        self::assertSame(200, $response->getStatusCode());
        $body = (string)$response->getBody();

        // Table with all three counters, the from→to range and the health badges rendered without errors.
        self::assertStringContainsString('Happy clients', $body);
        self::assertStringContainsString('1,200', $body); // uid 10 counter_last formatted
        self::assertStringContainsString('Won&#039;t animate (start = end)', $body); // uid 11
        self::assertSame(3, substr_count($body, '%5Btt_content%5D'), 'one edit link per counter');
        self::assertStringContainsString('href="https://www.pixelegg.de" target="_blank" rel="noopener"', $body);
        self::assertStringNotContainsString('pxecnt', $body);
    }

    #[Test]
    public function indexActionRendersEmptyStateWithoutCounters(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)
            ->createFromUserPreferences($backendUser);

        $controller = $this->get(OverviewController::class);
        $response = $controller->indexAction($this->buildRequest());

        self::assertSame(200, $response->getStatusCode());
        $body = (string)$response->getBody();
        self::assertStringContainsString('callout-info', $body);
        self::assertStringContainsString('No Counter elements yet', $body);
        self::assertStringNotContainsString('<table', $body);
    }
}
