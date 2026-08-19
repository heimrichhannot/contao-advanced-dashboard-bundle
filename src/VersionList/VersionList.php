<?php

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Pagination\PaginationInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Database;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use HeimrichHannot\AdvancedDashboardBundle\Event\VersionListRowEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class VersionList
{
    public function __construct(
        private array               $rows,
        private PaginationInterface $pagination,
    ) {}

    public function pagination(): PaginationInterface
    {
        return $this->pagination;
    }

    public function rows(): array
    {
        return $this->rows;
    }
}