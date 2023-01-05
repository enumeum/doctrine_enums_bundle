<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnumBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_enum');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->beforeNormalization()
                ->ifTrue(static function ($v) {
                    return is_array($v) && !array_key_exists('connections', $v) && !array_key_exists('connection', $v);
                })
                ->then(static function ($v) {
                    $connection = [];
                    foreach ($v as $key => $value) {
                        $connection[$key] = $value;
                        unset($v[$key]);
                    }
                    $v['connections'] = ['default' => $connection];

                    return $v;
                })
            ->end()
            ->fixXmlConfig('connection')
            ->append($this->getConnectionsNode())
        ->end()
        ;

        return $treeBuilder;
    }

    private function getConnectionsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('connections');
        $node = $treeBuilder->getRootNode();

        $connectionNode = $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');
        assert($connectionNode instanceof ArrayNodeDefinition);

        $connectionNode
            ->fixXmlConfig('type')
            ->fixXmlConfig('path')
            ->children()
                ->arrayNode('types')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('paths')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('dir')->end()
                        ->scalarNode('namespace')->end()
                    ->end()
                ->end()
                ->end()
                ->end()
            ->end();

        return $node;
    }
}
