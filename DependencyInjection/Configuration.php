<?php

namespace Bangpound\Bundle\DrupalBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('bangpound_drupal');

        $rootNode
            ->children()
                ->scalarNode('url')->defaultValue('')->end()
                ->scalarNode('prefix')->defaultValue('')->end()
                ->arrayNode('conf')
                    ->defaultValue(array(
                        'session_inc' => 'sites/all/modules/symfony-module/session.inc',
                        'mail_system' => array(
                            'default-system' => '%bangpound_drupal.mail_system.class%',
                        )
                    ))
                    ->prototype('variable')
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        return $treeBuilder;
    }
}
