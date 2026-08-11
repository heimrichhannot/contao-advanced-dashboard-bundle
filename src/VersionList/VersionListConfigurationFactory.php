<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\VersionList;

use Contao\BackendUser;
use Symfony\Bundle\SecurityBundle\Security;

class VersionListConfigurationFactory
{
    public function __construct(private readonly Security $security, private readonly array $bundleConfig)
    {
    }

    public function createConfigurationForCurrentUser(): VersionListConfiguration
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return new VersionListConfiguration([], [], 0);
        }

        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            throw new \LogicException('The advanced dashboard can only be rendered for a Contao back end user.');
        }

        $tables = null;
        $columns = null;
        $userLevel = VersionListConfiguration::USER_ACCESS_LEVEL_SELF;
        $allowedUsers = (int) $user->id;
        $hasUserConfig = false;

        foreach ($this->bundleConfig['versions_rights'] as $configName => $config) {
            if (!$this->security->isGranted('contao_user.huhAdvDash_versionsRights', $configName)) {
                continue;
            }

            $hasUserConfig = true;
            $tables = $this->mergeRestrictions($tables, $config['tables']);
            $columns = $this->mergeRestrictions($columns, $config['columns']);

            if (VersionListConfiguration::USER_ACCESS_LEVEL_ALL === $config['user_access_level']) {
                $userLevel = VersionListConfiguration::USER_ACCESS_LEVEL_ALL;
            }
        }

        if (!$hasUserConfig) {
            $defaultConfig = $this->bundleConfig['versions_rights']['default'];
            $tables = $defaultConfig['tables'];
            $columns = $defaultConfig['columns'];
            $userLevel = $defaultConfig['user_access_level'];
        }

        if (VersionListConfiguration::USER_ACCESS_LEVEL_ALL === $userLevel) {
            $allowedUsers = 0;
        }

        return new VersionListConfiguration($tables ?? [], $columns ?? [], $allowedUsers);
    }

    /**
     * @param list<string>|null $current
     * @param list<string>      $next
     *
     * @return list<string>
     */
    private function mergeRestrictions(?array $current, array $next): array
    {
        if ([] === $current || [] === $next) {
            return [];
        }

        return array_values(array_unique([...($current ?? []), ...$next]));
    }
}
