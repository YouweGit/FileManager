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

        $default_mimetypes = array(
            'image/png',
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'application/pdf',
            'application/ogg',
            'video/mp4',
            'application/zip',
            'multipart/x-zip',
            'application/rar',
            'application/x-rar-compressed',
            'application/x-zip-compressed',
            'application/tar',
            'application/x-tar',
            'text/plain',
            'text/x-asm',
            'application/octet-stream'
        );

        $rootNode = $treeBuilder->root('youwe_media');
        $rootNode
            ->children()
                ->arrayNode('mime_allowed')
                    ->prototype('scalar')->defaultValue($default_mimetypes)->cannotBeEmpty()->end()
                ->end()
                ->booleanNode('full_exception')
                    ->defaultFalse()
                ->end()
                ->scalarNode('upload_path')
                    ->defaultValue('%kernel.root_dir%/../web/uploads')->cannotBeEmpty()
                ->end()
                ->arrayNode('theme')
                    ->children()
                        ->scalarNode('css')
                            ->defaultValue("/bundles/youwemedia/css/simple/media.css")->cannotBeEmpty()
                        ->end()
                        ->scalarNode('template')
                            ->defaultValue('YouweMediaBundle:Media:media.html.twig')->cannotBeEmpty()
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->scalarNode('usage_class')
                    ->defaultFalse()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
