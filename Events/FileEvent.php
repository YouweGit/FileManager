<?php
namespace Youwe\FileManagerBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use Youwe\FileManagerBundle\Model\FileInfo;

/**
 * Class FileEvent
 * @package Youwe\FileManagerBundle\Events
 */
class FileEvent extends Event
{
    /** @var FileInfo */
    protected $fileInfo;

    /**
     * @param FileInfo|null $fileInfo
     */
    public function __construct(FileInfo $fileInfo = null)
    {
        $this->fileInfo = $fileInfo;
    }

    /**
     * @return string
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }
}