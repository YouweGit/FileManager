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
    private $file_size;

    /** @var  string */
    private $modified;

    /** @var  int */
    private $usages;


    /** @var  string */
    private $fileclass;

    /** @var  string */
    private $web_path;

    /**
     * @param       $filepath
     * @param Media $media
     */
    public function __construct($filepath, Media $media)
    {
        $filename = basename($filepath);

        $file_size = Utils::readableSize(filesize($filepath));
        $file_modification = date("Y-m-d H:i:s", filemtime($filepath));
        $mimetype = mime_content_type($filepath);
        $web_path = Utils::DirTrim($media->getPath($media->getDirPath()), $filename, true);

        $this->setFilename($filename);
        $this->setMimetype($mimetype);
        $this->setModified($file_modification);
        $this->setFileSize($file_size);
        $this->setFilepath($filepath);
        $this->setWebPath($web_path);
        $this->setUsages($media);
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

        if (isset(Utils::$humanReadableTypes[$mimetype])) {
            $this->setReadableType(Utils::$humanReadableTypes[$mimetype]);
        } else {
            $this->setReadableType("Undefined");
        }
        $this->setFileclass(Utils::getFileClass($mimetype));
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
    public function getFileSize()
    {
        return $this->file_size;
    }

    /**
     * @param string $file_size
     */
    public function setFileSize($file_size)
    {
        $this->file_size = $file_size;
    }

    /**
     * @return int
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * @param Media $media
     */
    private function setUsages(Media $media)
    {
        $usages = array();
        $usage_class = $media->getUsagesClass();
        if ($usage_class != false) {
            /** @var mixed $usage_object */
            $usage_object = new $usage_class;
            $usages_result = $usage_object->returnUsages($this->getFilepath());
            if (!empty($usages_result)) {
                $usages = $usages_result;
            }
        }
        $this->usages = count($usages);
    }

    /**
     * @param bool $trim
     * @return string
     */
    public function getWebPath($trim = false)
    {
        if($trim) {
            return trim($this->web_path, DIRECTORY_SEPARATOR);
        } else {
            return $this->web_path;
        }
    }

    /**
     * @param string $web_path
     */
    public function setWebPath($web_path)
    {
        $this->web_path = $web_path;
    }

    /**
     * @return bool
     */
    public function isDir()
    {
        return is_dir($this->getFilepath());
    }

    /**
     * @author Jim Ouwerkerk
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

}