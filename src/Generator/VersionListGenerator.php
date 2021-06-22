<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Generator;

use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\FilesModel;
use Contao\Input;
use Contao\Pagination;
use Contao\StringUtil;
use HeimrichHannot\AdvancedDashboardBundle\EventListener\VersionListDatabaseColumnsEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class VersionListGenerator
{
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function generate(): array
    {
        $versions = [];

        $objUser = BackendUser::getInstance();
        $objDatabase = Database::getInstance();

        // Get the total number of versions
        $objTotal = $objDatabase->prepare('SELECT COUNT(*) AS count FROM tl_version WHERE editUrl IS NOT NULL'.(!$objUser->isAdmin ? ' AND userid=?' : ''))
            ->execute($objUser->id);

        $intLast = ceil($objTotal->count / 30);
        $intPage = Input::get('vp') ?? 1;
        $intOffset = ($intPage - 1) * 30;

        // Validate the page number
        if ($intPage < 1 || ($intLast > 0 && $intPage > $intLast)) {
            header('HTTP/1.1 404 Not Found');
        }

        // Create the pagination menu
        $objPagination = new Pagination($objTpotal->count, 30, 7, 'vp', new BackendTemplate('be_pagination'));
        $objTemplate->pagination = $objPagination->generate();

        $defaultFields = ['pid', 'tstamp', 'version', 'fromTable', 'username', 'userid', 'description', 'editUrl', 'active'];

        /** @var VersionListDatabaseColumnsEvent $event */
        $event = $this->eventDispatcher->dispatch(new VersionListDatabaseColumnsEvent($defaultFields));

        $fields = implode(', ', $event->getFields());

        // Get the versions
        $objVersions = $objDatabase->prepare("SELECT $fields FROM tl_version WHERE editUrl IS NOT NULL".(!$objUser->isAdmin ? ' AND userid=?' : '').' ORDER BY tstamp DESC, pid, version DESC')
            ->limit(30, $intOffset)
            ->execute($objUser->id);

        while ($objVersions->next()) {
            // Hide profile changes if the user does not have access to the "user" module (see #1309)
            if (!$objUser->isAdmin && 'tl_user' == $objVersions->fromTable && !$objUser->hasAccess('user', 'modules')) {
                continue;
            }

            $arrRow = $objVersions->row();

            // Add some parameters
            $arrRow['from'] = max(($objVersions->version - 1), 1); // see #4828
            $arrRow['to'] = $objVersions->version;
            $arrRow['date'] = date(Config::get('datimFormat'), $objVersions->tstamp);
            $arrRow['description'] = StringUtil::substr($arrRow['description'], 32);
            $arrRow['shortTable'] = StringUtil::substr($arrRow['fromTable'], 18); // see #5769

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
                $objDeleted = $objDatabase->prepare('SELECT COUNT(*) AS count FROM '.$v['fromTable'].' WHERE id=?')
                    ->execute($v['pid']);

                $versions[$k]['deleted'] = ($objDeleted->count < 1);
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
}
