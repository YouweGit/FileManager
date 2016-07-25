<?php

namespace Youwe\FileManagerBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Youwe\FileManagerBundle\Model\FileManager;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerExtension
 * @package Youwe\FileManagerBundle\Twig
 */
class FileManagerExtension extends \Twig_Extension
{
    /** @var ContainerInterface  */
    private $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('thumb', [$this, 'thumb']),
        );
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     *
     * @return \Twig_Markup
     */
    public function thumb($path)
    {
        try{
            $cacheManager = $this->container->get('liip_imagine.cache.manager');
            $filter = FileManager::FILTER_NAME;
            return new \Twig_Markup(
                $cacheManager->getBrowserPath($path, $filter, array()),
                'utf8'
            );
        } catch(\Exception $e){
            return $path;
        }

    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'youwe_filemanager';
    }
}
