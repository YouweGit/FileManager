<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileDirCreated
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileDirCreated implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::BEFORE_FILE_DIR_CREATED => 'beforeFileDirCreated',
            YouweFileManagerEvents::AFTER_FILE_DIR_CREATED  => 'afterFileDirCreated',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileDirCreated(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileDirCreated(FileEvent $event)
    {
    }

}