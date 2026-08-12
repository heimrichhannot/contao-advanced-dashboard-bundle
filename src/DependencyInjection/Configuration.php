<?php

declare(strict_types=1);

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\AdvancedDashboardBundle\DependencyInjection;

use HeimrichHannot\AdvancedDashboardBundle\VersionList\AccessLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('huh_advanced_dashboard');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('versions_rights')
                    ->info('Configure user rights for version list. Can be selected in the user and user group settings.')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->info("The title of the configuration. Should be a unique alias/name containing just 'a-z0-9-_' like 'all_users','editor_news'.")
                        ->children()
                            ->arrayNode('tables')
                                ->info('Allowed database tables. Empty means all tables are allowed.')
                                ->defaultValue([])
                                ->scalarPrototype()->end()
                            ->end()
                            ->enumNode('user_access_level')
                                ->info('Access rights for other users version logs.')
                                ->values(array_column(AccessLevel::cases(), 'value'))
                                ->defaultValue(AccessLevel::SELF->value)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
