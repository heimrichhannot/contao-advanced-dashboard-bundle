<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\Tests\DependencyInjection;

use HeimrichHannot\AdvancedDashboardBundle\DependencyInjection\HeimrichHannotAdvancedDashboardExtension;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HeimrichHannotAdvancedDashboardExtensionTest extends TestCase
{
    public function testAddsACompleteDefaultPermissionConfiguration(): void
    {
        $container = new ContainerBuilder();
        (new HeimrichHannotAdvancedDashboardExtension())->load([], $container);

        self::assertSame(
            [
                'versions_rights' => [
                    'default' => [
                        'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_SELF,
                        'columns' => VersionListGenerator::DEFAULT_COLUMNS,
                        'tables' => [],
                    ],
                ],
            ],
            $container->getParameter('huh_advanced_dashboard'),
        );
    }

    public function testKeepsAnExplicitlyUnrestrictedDefaultConfiguration(): void
    {
        $container = new ContainerBuilder();
        (new HeimrichHannotAdvancedDashboardExtension())->load(
            [
                [
                    'versions_rights' => [
                        'default' => [
                            'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_ALL,
                            'columns' => [],
                            'tables' => ['tl_news'],
                        ],
                    ],
                ],
            ],
            $container,
        );

        self::assertSame(
            [
                'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_ALL,
                'columns' => [],
                'tables' => ['tl_news'],
            ],
            $container->getParameter('huh_advanced_dashboard')['versions_rights']['default'],
        );
    }
}
