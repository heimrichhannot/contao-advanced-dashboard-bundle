<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Symfony\Component\Security\Core\Security;

class VersionListConfigurationFactory
{
    /** @var Security */
    protected $security;

    /** @var array */
    protected $bundleConfig;

    /**
     * VersionListConfigurationFactory constructor.
     */
    public function __construct(Security $security, array $bundleConfig)
    {
        $this->security = $security;
        $this->bundleConfig = $bundleConfig;
    }

    public function createConfigurationForCurrentUser(): VersionListConfiguration
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return new VersionListConfiguration([], [], 0);
        }

        $configs = array_keys($this->bundleConfig['versions_rights']);

        $tables = null;
        $columns = null;
        $userLevel = VersionListConfiguration::USER_ACCESS_LEVEL_SELF;
        $allowedUsers = $this->security->getUser()->id;
        $userConfigs = [];

        if (!empty($configs)) {
            foreach ($configs as $configName) {
                if (!$this->security->isGranted('contao_user.huhAdvDash_versionsRights', $configName)) {
                    continue;
                }
                $userConfigs[] = $configName;
                $config = &$this->bundleConfig['versions_rights'][$configName];

                if (!\is_array($tables) || !empty($tables)) {
                    if (empty($config['tables'])) {
                        $tables = [];
                    } else {
                        $tables = array_merge($tables ?? [], $config['tables']);
                    }
                }

                if (!\is_array($columns) || !empty($columns)) {
                    if (empty($config['columns'])) {
                        $columns = [];
                    } else {
                        $columns = array_merge($columns ?? [], $config['columns']);
                    }
                }

                if (VersionListConfiguration::USER_ACCESS_LEVEL_ALL !== $userLevel) {
                    if (VersionListConfiguration::USER_ACCESS_LEVEL_ALL === $config['user_access_level']) {
                        $userLevel = VersionListConfiguration::USER_ACCESS_LEVEL_ALL;
                    }
                }
            }
        }

        if (empty($userConfigs)) {
            $tables = $this->bundleConfig['versions_rights']['default']['tables'] ?? [];
            $columns = $this->bundleConfig['versions_rights']['default']['columns'] ?? [];
            $userLevel = $this->bundleConfig['versions_rights']['default']['user_access_level'];
        }

        switch ($userLevel) {
            case VersionListConfiguration::USER_ACCESS_LEVEL_ALL:
                $allowedUsers = 0;
        }

        return new VersionListConfiguration($tables, $columns, $allowedUsers);
    }
}
