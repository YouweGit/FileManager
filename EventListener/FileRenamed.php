<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileRenamed
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileRenamed implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_RENAMED  => 'afterFileRenamed',
            YouweFileManagerEvents::BEFORE_FILE_RENAMED => 'beforeFileRenamed',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileRenamed(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileRenamed(FileEvent $event)
    {
    }
}