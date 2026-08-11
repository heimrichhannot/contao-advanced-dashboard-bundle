<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\DependencyInjection;

use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class HeimrichHannotAdvancedDashboardExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $config['versions_rights']['default'] = array_replace(
            [
                'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_SELF,
                'columns' => VersionListGenerator::DEFAULT_COLUMNS,
                'tables' => [],
            ],
            $config['versions_rights']['default'] ?? [],
        );

        $container->setParameter('huh_advanced_dashboard', $config);
    }

    public function getAlias(): string
    {
        return 'huh_advanced_dashboard';
    }
}
