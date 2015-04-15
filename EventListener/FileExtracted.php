<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileExtracted
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileExtracted implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_EXTRACTED => 'afterFileExtracted',
            YouweFileManagerEvents::BEFORE_FILE_EXTRACTED => 'beforeFileExtracted',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileExtracted(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileExtracted(FileEvent $event)
    {
    }
}