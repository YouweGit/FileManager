<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileDeleted
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileDeleted implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_DELETED => 'afterFileDeleted',
            YouweFileManagerEvents::BEFORE_FILE_DELETED => 'beforeFileDeleted',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileDeleted(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileDeleted(FileEvent $event)
    {
    }
}