<?php
namespace Youwe\FileManagerBundle\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * Class FileUploaded
 * @package Youwe\FileManagerBundle\EventListener
 */
class FileUploaded implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            YouweFileManagerEvents::AFTER_FILE_UPLOADED  => 'afterFileUploaded',
            YouweFileManagerEvents::BEFORE_FILE_UPLOADED => 'beforeFileUploaded',
        );
    }

    /**
     * @param FileEvent $event
     */
    public function afterFileUploaded(FileEvent $event)
    {
    }

    /**
     * @param FileEvent $event
     */
    public function beforeFileUploaded(FileEvent $event)
    {
    }
}