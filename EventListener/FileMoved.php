<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileMoved
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileMoved implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_MOVED  => 'afterFileMoved',
            YouweFileManagerEvents::BEFORE_FILE_MOVED => 'beforeFileMoved',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileMoved(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileMoved(FileEvent $event)
    {
    }
}