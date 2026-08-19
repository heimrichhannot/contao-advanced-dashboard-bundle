<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class UserGroupContainer
{
    public function __construct(private readonly array $bundleConfig)
    {
    }

    #[AsCallback(table: 'tl_user', target: 'fields.huhAdvDash_versionsRights.options')]
    #[AsCallback(table: 'tl_user_group', target: 'fields.huhAdvDash_versionsRights.options')]
    public function onVersionsRightsOptionsCallback(?DataContainer $dc): array
    {
        return array_keys($this->bundleConfig['versions_rights']);
    }
}
