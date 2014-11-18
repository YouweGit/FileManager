<?php

namespace Youwe\MediaBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Youwe\MediaBundle\Services\Utils;

/**
 * Class FileInfo
 * @package Youwe\MediaBundle\Model
 */
class FileInfo
{
    /** @var  string */
    private $filename;

    /** @var  string */
    private $filepath;

    /** @var  string */
    private $mimetype;

    /** @var  string */
    private $readableType;

    /** @var  string */
    private $size;

    /** @var  string */
    private $modified;

    /** @var  int */
    private $usages;

    /** @var  array */
    private $usages_locations;

    /** @var  string */
    private $fileclass;

    /**
     * @param $filepath
     */
    public function __construct($filepath)
    {
        $filename = basename($filepath);

        $file_size = Utils::readableSize(filesize($filepath));
        $file_modification = date("Y-m-d H:i:s", filemtime($filepath));
        $mimetype = mime_content_type($filepath);

        $this->setFilename($filename);
        $this->setMimetype($mimetype);
        $this->setModified($file_modification);
        $this->setSize($file_size);
        $this->setFilepath($filepath);
    }

    /**
     * @return string
     */
    public function getFileclass()
    {
        return $this->fileclass;
    }

    /**
     * @param string $fileclass
     */
    public function setFileclass($fileclass)
    {
        $this->fileclass = $fileclass;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * @param string $filepath
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * @param string $mimetype
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    /**
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param string $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return string
     */
    public function getReadableType()
    {
        return $this->readableType;
    }

    /**
     * @param string $readableType
     */
    public function setReadableType($readableType)
    {
        $this->readableType = $readableType;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * @param int $usages
     */
    public function setUsages($usages)
    {
        $this->usages = $usages;
    }

    /**
     * @return array
     */
    public function getUsagesLocations()
    {
        return $this->usages_locations;
    }

    /**
     * @param array $usages_locations
     */
    public function setUsagesLocations($usages_locations)
    {
        $this->usages_locations = $usages_locations;
    }

}