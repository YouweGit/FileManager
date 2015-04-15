<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FilePasted
 * @package Youwe\FileManagerBundle\EventListener
 */
class FilePasted implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_PASTED => 'afterFilePasted',
            YouweFileManagerEvents::BEFORE_FILE_PASTED => 'beforeFilePasted',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFilePasted(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFilePasted(FileEvent $event)
    {
    }
}