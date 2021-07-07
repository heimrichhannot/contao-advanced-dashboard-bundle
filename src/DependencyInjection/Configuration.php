<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\DependencyInjection;

use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListConfiguration;
use HeimrichHannot\AdvancedDashboardBundle\VersionList\VersionListGenerator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('huh_advanced_dashboard');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('versions_rights')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('columns')
                                ->defaultValue(array_keys(VersionListGenerator::columns()))
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('tables')
                                ->scalarPrototype()->end()
                            ->end()
                            ->enumNode('user_access_level')
                                ->values([VersionListConfiguration::USER_ACCESS_LEVEL_ALL, VersionListConfiguration::USER_ACCESS_LEVEL_SELF])
                                ->defaultValue([VersionListConfiguration::USER_ACCESS_LEVEL_SELF])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
