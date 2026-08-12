<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Pagination\PaginationFactory;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FilesModel;
use Contao\Image;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListDatabaseColumnsEvent;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListTableColumnsEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VersionListGenerator
{
    public const DEFAULT_COLUMNS = [
        'date',
        'user',
        'table',
        'id',
        'description',
        'version',
        'actions',
    ];

    private const ITEMS_PER_PAGE = 30;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Connection $connection,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly PaginationFactoryInterface $paginationFactory,
    ) {
    }

    /**
     * @return array{versions: list<array{class: string, cols: array<string, string>}>, columns: array<string, array>, pagination: string}
     */
    public function generate(VersionListConfiguration $configuration): array
    {
        [$filter, $parameters, $types] = $this->createFilter($configuration);

        $versionCount = (int) $this->connection
            ->executeQuery('SELECT COUNT(*) FROM tl_version WHERE '.$filter, $parameters, $types)
            ->fetchOne()
        ;

        $lastPage = max(1, (int) ceil($versionCount / self::ITEMS_PER_PAGE));
        $requestedPage = $this->requestStack->getCurrentRequest()?->query->getInt('vp', 1) ?? 1;
        $page = min(max(1, $requestedPage), $lastPage);
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;

        $defaultDatabaseColumns = ['pid', 'tstamp', 'version', 'fromTable', 'username', 'userid', 'description', 'editUrl', 'active'];
        $databaseColumnsEvent = $this->eventDispatcher->dispatch(new VersionListDatabaseColumnsEvent($defaultDatabaseColumns));
        $databaseColumns = $databaseColumnsEvent->getColumns();

        if ([] === $databaseColumns) {
            throw new \LogicException('At least one version database column must be selected.');
        }

        $fields = implode(', ', array_map(fn (string $column): string => $this->connection->quoteIdentifier($column), $databaseColumns));
        $sql = sprintf(
            'SELECT %s FROM tl_version WHERE %s ORDER BY tstamp DESC, pid, version DESC LIMIT %d, %d',
            $fields,
            $filter,
            $offset,
            self::ITEMS_PER_PAGE,
        );

        $versions = $this->prepareRows($this->connection->executeQuery($sql, $parameters, $types));
        $columns = $this->eventDispatcher->dispatch(new VersionListTableColumnsEvent($this->createDefaultColumns()))->getColumns();

        if ([] !== $configuration->getColumns()) {
            $allowedColumns = $configuration->getColumns();
            $columns = array_filter(
                $columns,
                static fn (string $key): bool => \in_array($key, $allowedColumns, true),
                ARRAY_FILTER_USE_KEY,
            );
        }

        return [
            'versions' => $this->renderRows($versions, $columns),
            'columns' => $columns,
            'pagination' => $this->paginationFactory->create((new PaginationConfig('vp', $versionCount, self::ITEMS_PER_PAGE))->withIgnoreOutOfBounds()),
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function createFilter(VersionListConfiguration $configuration): array
    {
        $filters = ['editUrl IS NOT NULL'];
        $parameters = [];
        $types = [];
        $allowedUsers = $configuration->getAllowedUsers();

        if (\is_array($allowedUsers)) {
            $filters[] = 'userid IN (:user_ids)';
            $parameters['user_ids'] = $allowedUsers;
            $types['user_ids'] = ArrayParameterType::INTEGER;
        } elseif (0 !== $allowedUsers) {
            $filters[] = 'userid = :user_id';
            $parameters['user_id'] = $allowedUsers;
        }

        if ([] !== $configuration->getTables()) {
            $filters[] = 'fromTable IN (:tables)';
            $parameters['tables'] = $configuration->getTables();
            $types['tables'] = ArrayParameterType::STRING;
        }

        return [implode(' AND ', $filters), $parameters, $types];
    }

    /** @return array<string, array{label?: string, class?: string, renderCallback: callable(array): string}> */
    private function createDefaultColumns(): array
    {
        return [
            'date' => [
                'label' => $this->translator->trans('MSC.date', [], 'contao_default'),
                'renderCallback' => static fn (array $version): string => $version['date'],
            ],
            'user' => [
                'label' => $this->translator->trans('MSC.user', [], 'contao_default'),
                'renderCallback' => static fn (array $version): string => $version['username'] ?: '-',
            ],
            'table' => [
                'label' => $this->translator->trans('MSC.table', [], 'contao_default'),
                'renderCallback' => static fn (array $version): string => $version['shortTable'],
            ],
            'id' => [
                'label' => 'ID',
                'renderCallback' => static fn (array $version): string => (string) $version['pid'],
            ],
            'description' => [
                'label' => $this->translator->trans('MSC.description', [], 'contao_default'),
                'renderCallback' => static fn (array $version): string => $version['description'] ?: '-',
            ],
            'version' => [
                'label' => $this->translator->trans('MSC.version', [], 'contao_default'),
                'renderCallback' => static fn (array $version): string => $version['active'] ? '<strong>'.$version['version'].'</strong>' : (string) $version['version'],
            ],
            'actions' => [
                'class' => 'tl_right_nowrap',
                'renderCallback' => fn (array $version): string => $this->renderRowActions($version),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function prepareRows(Result $result): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            throw new \LogicException('The advanced dashboard can only be rendered for a Contao back end user.');
        }

        $versions = [];

        while ($rawRow = $result->fetchAssociative()) {
            if (!$user->isAdmin && 'tl_user' === $rawRow['fromTable'] && !$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user')) {
                continue;
            }

            $row = $rawRow;
            $row['from'] = max(((int) $rawRow['version']) - 1, 1);
            $row['to'] = (int) $rawRow['version'];
            $row['date'] = date(Config::get('datimFormat'), (int) $rawRow['tstamp']);
            $row['description'] = StringUtil::substr((string) $row['description'], 32);
            $row['shortTable'] = StringUtil::substr((string) $row['fromTable'], 18);
            $row['raw'] = $rawRow;

            if (isset($row['editUrl'])) {
                if ('tl_files' === $row['fromTable'] && ($filesModel = FilesModel::findById($row['pid']))) {
                    $row['editUrl'] = preg_replace('/id=[^&]+/', 'id='.$filesModel->path, $row['editUrl']);
                }

                $requestToken = self::escape($this->csrfTokenManager->getDefaultTokenValue());
                $editUrl = preg_replace(
                    ['/&(amp;)?popup=1/', '/&(amp;)?rt=[^&]+/'],
                    ['', '&amp;rt='.$requestToken],
                    StringUtil::ampersand(ltrim((string) $row['editUrl'], '/')),
                );
                $basePath = $this->requestStack->getCurrentRequest()?->getBasePath() ?? '';
                $row['editUrl'] = rtrim($basePath, '/').'/'.$editUrl;
            }

            $versions[] = $row;
        }

        foreach ($versions as $index => $version) {
            $versions[$index]['class'] = 0 === $index % 2 ? 'even' : 'odd';

            try {
                $table = $this->connection->quoteIdentifier((string) $version['fromTable']);
                $deletedCount = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$table.' WHERE id = ?', [$version['pid']]);
                $versions[$index]['deleted'] = $deletedCount < 1;
            } catch (Exception) {
                unset($versions[$index]);

                continue;
            }

            if ('tl_files' === $version['fromTable'] && $versions[$index]['deleted']) {
                unset($versions[$index]);
            }
        }

        return array_values($versions);
    }

    /**
     * @param list<array<string, mixed>> $versions
     * @param array<string, array>       $columns
     *
     * @return list<array{class: string, cols: array<string, string>}>
     */
    private function renderRows(array $versions, array $columns): array
    {
        $rows = [];

        foreach ($versions as $version) {
            $row = ['class' => $version['class'], 'cols' => []];

            foreach ($columns as $key => $column) {
                $callback = $column['renderCallback'] ?? null;
                $row['cols'][$key] = \is_callable($callback) ? (string) $callback($version) : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function renderRowActions(array $version): string
    {
        if ($version['deleted']) {
            $route = $this->router->generate('contao_backend', ['do' => 'undo']);
            $title = $this->translator->trans('MSC.restore', [], 'contao_default');

            return '<a href="'.self::escape($route).'" title="'.self::escape($title).'">'.Image::getHtml('undo.svg', '', 'class="undo"').'</a>';
        }

        $actions = '';
        $editTitle = $this->translator->trans('MSC.editElement', [], 'contao_default');

        if ($version['editUrl']) {
            $actions .= '<a href="'.$version['editUrl'].'" title="'.self::escape($editTitle).'" class="edit">'.Image::getHtml('edit.svg', '', 'style="padding:0 2px"').'</a>';
        } else {
            $actions .= Image::getHtml('edit--disabled.svg', '', 'style="padding:0 2px"');
        }

        if ($version['editUrl'] && $version['to'] > 1) {
            $showDifferences = $this->translator->trans('MSC.showDifferences', [], 'contao_default');
            $recordOfTable = $this->translator->trans('MSC.recordOfTable', [$version['pid'], $version['fromTable']], 'contao_default');
            $onclick = sprintf(
                'Backend.openModalIframe({title:%s,url:this.href});return false',
                json_encode($recordOfTable, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_THROW_ON_ERROR),
            );
            $href = $version['editUrl'].'&amp;from='.$version['from'].'&amp;to='.$version['to'].'&amp;versions=1&amp;popup=1';
            $actions .= '<a href="'.$href.'" title="'.self::escape($showDifferences).'" onclick="'.self::escape($onclick).'">'.Image::getHtml('diff.svg').'</a>';
        } else {
            $actions .= Image::getHtml('diff--disabled.svg');
        }

        return $actions;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
    }
}
