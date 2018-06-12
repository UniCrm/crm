<?php

namespace UniCrm\Bundles\CalendarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 * The allowed config keyes are as defined below
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('uni_crm_calendar');

        $rootNode
            ->children()
                ->arrayNode('google')
                    ->children()
                        ->scalarNode('application_name')->end()
                        ->scalarNode('redirect_route')->end()
                        ->scalarNode('client_id')->end()
                        ->scalarNode('project_id')->end()
                        ->scalarNode('auth_uri')->end()
                        ->scalarNode('token_uri')->end()
                        ->scalarNode('auth_provider_x509_cert_url')->end()
                        ->scalarNode('client_secret')->end()
                        ->scalarNode('auth_provider_x509_cert_url')->end()
                    ->end()
                ->end()

                ->arrayNode('outlook')
                    ->children()
                       ->scalarNode('client_id')->end()
                       ->scalarNode('client_secret')->end()
                       ->scalarNode('redirect_route')->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
