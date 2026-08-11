<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\VersionList;

use Contao\BackendUser;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class VersionListConfigurationFactoryTest extends TestCase
{
    public function testAdministratorReceivesUnrestrictedConfiguration(): void
    {
        $checkedAttributes = [];
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturnCallback(
            static function (mixed $attribute) use (&$checkedAttributes): bool {
                $checkedAttributes[] = $attribute;

                return true;
            },
        );

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame(['ROLE_ADMIN'], $checkedAttributes);
        self::assertSame([], $configuration->getTables());
        self::assertSame([], $configuration->getColumns());
        self::assertSame(0, $configuration->getAllowedUsers());
    }

    public function testUsesTheDefaultConfigurationWithoutAssignedRights(): void
    {
        $security = $this->createSecurity([], 42);

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame(['tl_content'], $configuration->getTables());
        self::assertSame(['date', 'actions'], $configuration->getColumns());
        self::assertSame(42, $configuration->getAllowedUsers());
    }

    public function testMergesAssignedRightsAndTreatsAnEmptyRestrictionAsUnrestricted(): void
    {
        $security = $this->createSecurity(['editor_news', 'editor_all'], 42);

        $configuration = (new VersionListConfigurationFactory($security, $this->createBundleConfig()))
            ->createConfigurationForCurrentUser()
        ;

        self::assertSame([], $configuration->getTables());
        self::assertSame(['date', 'description', 'actions'], $configuration->getColumns());
        self::assertSame(0, $configuration->getAllowedUsers());
    }

    private function createSecurity(array $grantedConfigurations, int $userId): Security
    {
        $user = new class($userId) extends BackendUser {
            public function __construct(int $userId)
            {
                $this->id = $userId;
                $this->admin = false;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturnCallback(
            static function (mixed $attribute, mixed $subject = null) use ($grantedConfigurations): bool {
                if ('ROLE_ADMIN' === $attribute) {
                    return false;
                }

                return 'contao_user.huhAdvDash_versionsRights' === $attribute && \in_array($subject, $grantedConfigurations, true);
            },
        );

        return $security;
    }

    private function createBundleConfig(): array
    {
        return [
            'versions_rights' => [
                'default' => [
                    'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_SELF,
                    'columns' => ['date', 'actions'],
                    'tables' => ['tl_content'],
                ],
                'editor_news' => [
                    'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_SELF,
                    'columns' => ['date', 'description'],
                    'tables' => ['tl_news'],
                ],
                'editor_all' => [
                    'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_ALL,
                    'columns' => ['actions', 'date'],
                    'tables' => [],
                ],
            ],
        ];
    }
}
