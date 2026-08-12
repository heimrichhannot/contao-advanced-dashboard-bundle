<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\VersionList;

use Contao\BackendUser;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\AccessLevel;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class VersionListConfigurationFactoryTest extends TestCase
{
    public function testAdministratorReceivesUnrestrictedConfiguration(): void
    {
        $user = $this->createBackendUser(1, [], true);
        $security = $this->createSecurity($user);

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame([], $configuration->getTables());
        self::assertSame(0, $configuration->getAllowedUsers());
    }

    public function testUsesTheDefaultConfigurationWithoutAssignedRights(): void
    {
        $security = $this->createSecurity($this->createBackendUser(42));

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame(['tl_content'], $configuration->getTables());
        self::assertSame(42, $configuration->getAllowedUsers());
    }

    public function testMergesAssignedRightsAndTreatsAnEmptyRestrictionAsUnrestricted(): void
    {
        $security = $this->createSecurity($this->createBackendUser(42, ['editor_news', 'editor_all']));

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame([], $configuration->getTables());
        self::assertSame(0, $configuration->getAllowedUsers());
    }

    public function testCreatesConfigurationForGivenUser(): void
    {
        $configuration = (new VersionListConfigurationFactory($this->createStub(Security::class), $this->createBundleConfig()))
            ->createConfigurationForUser($this->createBackendUser(84, ['editor_news']))
        ;

        self::assertSame(['tl_news'], $configuration->getTables());
        self::assertSame(84, $configuration->getAllowedUsers());
    }

    private function createSecurity(BackendUser $user): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }

    /** @param list<string> $versionRights */
    private function createBackendUser(int $userId, array $versionRights = [], bool $admin = false): BackendUser
    {
        return new class($userId, $versionRights, $admin) extends BackendUser {
            public function __construct(int $userId, array $versionRights, bool $admin)
            {
                $this->id = $userId;
                $this->admin = $admin;
                $this->huhAdvDash_versionsRights = $versionRights;
            }
        };
    }

    private function createBundleConfig(): array
    {
        return [
            'versions_rights' => [
                'default' => [
                    'user_access_level' => AccessLevel::SELF->value,
                    'tables' => ['tl_content'],
                ],
                'editor_news' => [
                    'user_access_level' => AccessLevel::SELF->value,
                    'tables' => ['tl_news'],
                ],
                'editor_all' => [
                    'user_access_level' => AccessLevel::ALL->value,
                    'tables' => [],
                ],
            ],
        ];
    }
}
