<?php

namespace Youwe\MediaBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('youwe_media');
        $rootNode
            ->children()
                ->arrayNode('mime_allowed')
                    ->prototype('scalar')->end()
                    ->defaultValue(array('image'))->cannotBeEmpty()
                ->end()
                ->scalarNode('upload_path')->defaultValue('/uploads')->cannotBeEmpty()->end()
                ->scalarNode('extended_template')->defaultValue('YouweMediaBundle:Media:media_layout.html.twig')->cannotBeEmpty()->end()
                ->scalarNode('template')->defaultValue('YouweMediaBundle:Media:media.html.twig')->cannotBeEmpty()->end()
                ->scalarNode('usage_class')->defaultValue(false)->end()
            ->end()
        ;
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
