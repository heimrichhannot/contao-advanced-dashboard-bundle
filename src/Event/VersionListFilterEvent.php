<?php

namespace HeimrichHannot\AdvancedDashboardBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use Symfony\Contracts\EventDispatcher\Event;

class VersionListFilterEvent extends Event
{
    public function __construct(
        public QueryBuilder             $queryBuilder,
        public VersionListConfiguration $config,
    ) {}
}