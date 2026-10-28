<?php

declare(strict_types=1);

namespace Pixelegg\PxeCounter\Service;

use Pixelegg\PxeCounter\Domain\CounterData;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Service\FlexFormService;

/**
 * Collects every Counter content element across the site for the backend module.
 */
final class CounterUsageService
{
    private const CTYPE = 'pxe_counter';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FlexFormService $flexFormService,
    ) {}

    /**
     * @return list<array{contentUid:int, pageUid:int, pageTitle:string, header:string, hidden:bool, languageUid:int, from:float, to:float, easing:string, easingValid:bool, incomplete:bool}>
     */
    public function getUsages(): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Deleted-restriction only: hidden rows must still be listed in the module.
        $qb->getRestrictions()->removeAll()->add(new DeletedRestriction());

        $rows = $qb
            ->select('c.uid', 'c.pid', 'c.header', 'c.hidden', 'c.sys_language_uid', 'c.pi_flexform')
            ->addSelect('p.title AS page_title')
            ->from('tt_content', 'c')
            ->leftJoin('c', 'pages', 'p', $qb->expr()->eq('p.uid', $qb->quoteIdentifier('c.pid')))
            ->where($qb->expr()->eq('c.CType', $qb->createNamedParameter(self::CTYPE)))
            ->orderBy('c.pid')
            ->addOrderBy('c.sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $usages = [];
        foreach ($rows as $row) {
            $flex = $this->parseFlexForm((string)($row['pi_flexform'] ?? ''));
            $from = (float)($flex['counter_first'] ?? 0);
            $to = (float)($flex['counter_last'] ?? 0);
            $hasValues = isset($flex['counter_first'], $flex['counter_last']);
            $easing = (string)($flex['easing'] ?? CounterData::DEFAULT_EASING);

            $usages[] = [
                'contentUid' => (int)$row['uid'],
                'pageUid' => (int)$row['pid'],
                'pageTitle' => (string)($row['page_title'] ?? ''),
                'header' => (string)($row['header'] ?? ''),
                'hidden' => (bool)$row['hidden'],
                'languageUid' => (int)$row['sys_language_uid'],
                'from' => $from,
                'to' => $to,
                'easing' => $easing,
                'easingValid' => CounterData::isValidEasing($easing),
                'incomplete' => CounterData::isIncomplete($hasValues, $from, $to),
            ];
        }

        return $usages;
    }

    /**
     * @param list<array{contentUid:int, pageUid:int, pageTitle:string, header:string, hidden:bool, languageUid:int, from:float, to:float, easing:string, easingValid:bool, incomplete:bool}> $usages
     * @return array{total:int, pages:int, hidden:int, incomplete:int}
     */
    public function getStatistics(array $usages): array
    {
        $pages = [];
        $hidden = 0;
        $incomplete = 0;
        foreach ($usages as $usage) {
            $pages[$usage['pageUid']] = true;
            if ($usage['hidden']) {
                $hidden++;
            }
            if ($usage['incomplete']) {
                $incomplete++;
            }
        }

        return [
            'total' => count($usages),
            'pages' => count($pages),
            'hidden' => $hidden,
            'incomplete' => $incomplete,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFlexForm(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        return $this->flexFormService->convertFlexFormContentToArray($xml);
    }
}
