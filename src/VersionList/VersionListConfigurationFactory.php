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

readonly class VersionListConfigurationFactory
{
    public function __construct(
        private Security $security,
        private array $bundleConfig
    ) {
    }

    public function createConfigurationForCurrentUser(): VersionListConfiguration
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            throw new \LogicException('The advanced dashboard can only be rendered for a Contao back end user.');
        }

        return $this->createConfigurationForUser($user);
    }

    public function createConfigurationForUser(BackendUser $user): VersionListConfiguration
    {
        if ($user->isAdmin) {
            return new VersionListConfiguration([], 0);
        }

        $tables = null;
        $userLevel = AccessLevel::SELF;
        $allowedUsers = (int) $user->id;
        $hasUserConfig = false;
        $versionRights = $user->huhAdvDash_versionsRights;

        foreach ($this->bundleConfig['versions_rights'] as $configName => $config) {
            if (!\is_array($versionRights) || !\in_array($configName, $versionRights, true)) {
                continue;
            }

            $hasUserConfig = true;
            $tables = $this->mergeRestrictions($tables, $config['tables']);

            if (AccessLevel::ALL === AccessLevel::from($config['user_access_level'])) {
                $userLevel = AccessLevel::ALL;
            }
        }

        if (!$hasUserConfig) {
            $defaultConfig = $this->bundleConfig['versions_rights']['default'];
            $tables = $defaultConfig['tables'];
            $userLevel = AccessLevel::from($defaultConfig['user_access_level']);
        }

        if (AccessLevel::ALL === $userLevel) {
            $allowedUsers = 0;
        }

        return new VersionListConfiguration($tables ?? [], $allowedUsers);
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
