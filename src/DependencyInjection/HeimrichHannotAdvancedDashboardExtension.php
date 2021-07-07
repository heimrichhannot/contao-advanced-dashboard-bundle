<?php

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
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        array_unshift($configs, ['versions_rights' => ['default' => []]]);
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['versions_rights']['default'])) {
            $config['versions_rights']['default'] = [
                'user_access_level' => VersionListConfiguration::USER_ACCESS_LEVEL_SELF,
                'columns' => array_keys(VersionListGenerator::columns()),
            ];
        }
        $container->setParameter('huh_advanced_dashboard', $config);
    }

    public function getAlias()
    {
        return 'huh_advanced_dashboard';
    }
}
