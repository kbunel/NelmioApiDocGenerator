<?php

namespace NelmioApiDocGenerator\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nelmio_api_doc_generator');

        $rootNode
            ->children()
                ->arrayNode('functions')
                    ->children()
                        ->arrayNode('serialization_groups')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('http_responses')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('return')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
