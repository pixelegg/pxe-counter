<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Controller\Backend;

use Pixelegg\PxeCounter\Service\CounterUsageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Backend module "Web > Counter": lists every Counter element with a health check.
 */
final class OverviewController
{
    private const LL = 'LLL:EXT:pxe_counter/Resources/Private/Language/Backend/locallang.xlf:';

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly CounterUsageService $usageService,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('pxe_counter');

        $usages = $this->usageService->getUsages();
        foreach ($usages as &$usage) {
            $usage['editUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['tt_content' => [$usage['contentUid'] => 'edit']],
                'returnUrl' => $returnUrl,
            ]);
            $usage['pageUrl'] = (string)$this->uriBuilder->buildUriFromRoute('web_layout', [
                'id' => $usage['pageUid'],
            ]);
        }
        unset($usage);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->getLanguageService()->sL(self::LL . 'module.title'));
        $view->assignMultiple([
            'usages' => $usages,
            'stats' => $this->usageService->getStatistics($usages),
        ]);

        return $view->renderResponse('Overview');
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
