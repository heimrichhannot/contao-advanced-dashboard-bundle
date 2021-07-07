<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\FilesModel;
use Contao\Image;
use Contao\Input;
use Contao\Pagination;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListDatabaseColumnsEvent;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListTableColumnsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class VersionListGenerator
{
    protected $eventDispatcher;
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(EventDispatcherInterface $eventDispatcher, Connection $connection, RouterInterface $router)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->connection = $connection;
        $this->router = $router;
    }

    public function generate(VersionListConfiguration $configuration): array
    {
        $userFilterValues = [];

        if (\is_array($configuration->getAllowedUsers())) {
            $user = array_filter($configuration->getAllowedUsers(), 'is_int');

            if (empty($user)) {
                throw new \InvalidArgumentException('User must be either integer or an array of integers.');
            }
            $userFilterQuery = ' AND userid IN ('.implode(', ', $user).')';
        } elseif (0 === $configuration->getAllowedUsers()) {
            $userFilterQuery = '';
        } else {
            $userFilterQuery = ' AND userid=?';
            $userFilterValues[] = $configuration->getAllowedUsers();
        }

        $tableFilterQuery = '';

        if (!empty($configuration->getTables())) {
            $tableFilterQuery = ' AND fromTable IN (\''.implode("','", $configuration->getTables()).'\')';
        }

        $stmt = $this->connection->prepare('SELECT COUNT(*) AS count FROM tl_version WHERE editUrl IS NOT NULL'.$userFilterQuery.$tableFilterQuery);
        $result = $stmt->executeQuery($userFilterValues);
        $versionCount = $result->fetchOne();

        $intLast = ceil($versionCount / 30);
        $intPage = Input::get('vp') ?? 1;
        $intOffset = ($intPage - 1) * 30;

        // Validate the page number
        if ($intPage < 1 || ($intLast > 0 && $intPage > $intLast)) {
            header('HTTP/1.1 404 Not Found');
        }

        $defaultDatabaseColumns = ['pid', 'tstamp', 'version', 'fromTable', 'username', 'userid', 'description', 'editUrl', 'active'];

        /** @var VersionListDatabaseColumnsEvent $event */
        $event = $this->eventDispatcher->dispatch(new VersionListDatabaseColumnsEvent($defaultDatabaseColumns));

        $fields = implode(', ', $event->getColumns());

        // Get the versions
        $stmt = $this->connection->prepare(
            "SELECT $fields FROM tl_version WHERE editUrl IS NOT NULL$userFilterQuery$tableFilterQuery ORDER BY tstamp DESC, pid, version DESC LIMIT $intOffset, 30"
        );
        $result = $stmt->executeQuery($userFilterValues);

        $versions = $this->prepareRows($result);

        $columns = $this->eventDispatcher->dispatch(new VersionListTableColumnsEvent(static::columns()))->getColumns();

        if (!empty($configuration->getColumns())) {
            $allowedColumns = $configuration->getColumns();
            $columns = array_filter($columns, function ($key) use ($allowedColumns) {
                return \in_array($key, $allowedColumns);
            }, ARRAY_FILTER_USE_KEY);
        }

        $versions = $this->renderRows($versions, $columns);

        return ['versions' => $versions, 'columns' => $columns, 'pagination' => $this->renderPagination($versionCount)];
    }

    public static function columns(): array
    {
        return [
            'date' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['date'],
                'renderCallback' => function (array $version) {
                    return $version['date'];
                },
            ],
            'user' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['user'],
                'renderCallback' => function (array $version) {
                    return $version['username'] ?: '-';
                },
            ],
            'table' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['table'],
                'renderCallback' => function (array $version) {
                    return $version['shortTable'];
                },
            ],
            'id' => [
                'label' => 'ID',
                'renderCallback' => function (array $version) {
                    return $version['pid'];
                },
            ],
            'description' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['description'],
                'renderCallback' => function (array $version) {
                    return $version['description'] ?: '-';
                },
            ],
            'version' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['version'],
                'renderCallback' => function (array $version) {
                    return $version['active'] ? '<strong>'.$version['version'].'</strong>' : $version['version'];
                },
            ],
            'actions' => [
                'renderCallback' => [static::class, 'renderRowActions'],
            ],
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function prepareRows(Result $result): array
    {
        $objUser = BackendUser::getInstance();
        $versions = [];

        while ($rawRow = $result->fetchAssociative()) {
            // Hide profile changes if the user does not have access to the "user" module (see #1309)
            if (!$objUser->isAdmin && 'tl_user' == $rawRow['fromTable'] && !$objUser->hasAccess('user', 'modules')) {
                continue;
            }

            $arrRow = $rawRow;

            // Add some parameters
            $arrRow['from'] = max(($rawRow['version'] - 1), 1); // see #4828
            $arrRow['to'] = $rawRow['version'];
            $arrRow['date'] = date(Config::get('datimFormat'), $rawRow['tstamp']);
            $arrRow['description'] = StringUtil::substr($arrRow['description'], 32);
            $arrRow['shortTable'] = StringUtil::substr($arrRow['fromTable'], 18); // see #5769
            $arrRow['raw'] = $rawRow;

            if (isset($arrRow['editUrl'])) {
                // Adjust the edit URL of files in case they have been renamed (see #671)
                if ('tl_files' == $arrRow['fromTable'] && ($filesModel = FilesModel::findByPk($arrRow['pid']))) {
                    $arrRow['editUrl'] = preg_replace('/id=[^&]+/', 'id='.$filesModel->path, $arrRow['editUrl']);
                }

                $arrRow['editUrl'] = preg_replace(['/&(amp;)?popup=1/', '/&(amp;)?rt=[^&]+/'], ['', '&amp;rt='.REQUEST_TOKEN], ampersand($arrRow['editUrl']));
            }

            $versions[] = $arrRow;
        }

        $intCount = -1;
        $versions = array_values($versions);

        // Add the "even" and "odd" classes
        foreach ($versions as $k => $v) {
            $versions[$k]['class'] = (0 == ++$intCount % 2) ? 'even' : 'odd';

            try {
                // Mark deleted versions (see #4336)
                $stmt = $this->connection->prepare('SELECT COUNT(*) AS count FROM '.$v['fromTable'].' WHERE id=?');
                $deletedCount = $stmt->executeStatement([$v['pid']]);

                $versions[$k]['deleted'] = ($deletedCount < 1);
            } catch (\Exception $e) {
                // Probably a disabled module
                --$intCount;
                unset($versions[$k]);
            }

            // Skip deleted files (see #8480)
            if ('tl_files' == $v['fromTable'] && $versions[$k]['deleted']) {
                --$intCount;
                unset($versions[$k]);
            }
        }

        return $versions;
    }

    protected function renderRows(array $versions, array $cols): array
    {
        $rows = [];

        foreach ($versions as $version) {
            $row = [];
            $row['class'] = $version['class'];

            foreach ($cols as $key => $col) {
                if (isset($col['renderCallback']) && \is_callable($col['renderCallback'])) {
                    $row['cols'][$key] = \call_user_func($col['renderCallback'], $version);
                } else {
                    $row['cols'][$key] = '';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    protected function renderRowActions(array $version): string
    {
        if ($version['deleted']) {
            $route = $this->router->generate('contao_backend', ['do' => 'undo']);

            return '<a href="'.$route.'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['restore']).'">'.Image::getHtml('undo.svg', '', 'class="undo"').'</a>';
        }
        $return = '';

        if ($version['editUrl']) {
            $return .= '<a href="'.$version['editUrl'].'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['editElement']).'" class="edit">'.Image::getHtml('edit.svg', '', 'style="padding:0 2px"').'</a>';
        } else {
            $return .= Image::getHtml('edit_.svg', '', 'style="padding:0 2px"');
        }

        if ($version['to'] > 1) {
            $return .= '<a href="'.$version['editUrl'].'&amp;from='.$version['from'].'&amp;to='.$version['to'].'&amp;versions=1&amp;popup=1" title="'.StringUtil::specialchars(str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['showDifferences'])).'" onclick="Backend.openModalIframe({\'title\':\''.sprintf(StringUtil::specialchars(str_replace("'", "\\'",
                        $GLOBALS['TL_LANG']['MSC']['recordOfTable'])), $version['pid'], $version['fromTable']).'\',\'url\':this.href});return false">'.Image::getHtml('diff.svg').'</a>';
        } else {
            $return .= Image::getHtml('diff_.svg');
        }

        return $return;
    }

    protected function renderPagination(int $versionCount): string
    {
        // Create the pagination menu
        $pagination = new Pagination($versionCount, 30, 7, 'vp', new BackendTemplate('be_pagination'));

        return $pagination->generate();
    }
}
