<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

class UserGroupContainer
{
    /** @var array */
    protected $bundleConfig;

    public function __construct(array $bundleConfig)
    {
        $this->bundleConfig = $bundleConfig;
    }

    /**
     * @Callback(table="tl_user", target="fields.huhAdvDash_versionsRights.options")
     * @Callback(table="tl_user_group", target="fields.huhAdvDash_versionsRights.options")
     */
    public function onVersionsRightsOptionsCallback(?DataContainer $dc): array
    {
        return array_keys($this->bundleConfig['versions_rights']);
    }
}
