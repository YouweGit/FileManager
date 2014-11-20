<?php

namespace Youwe\MediaBundle\Model;

use Symfony\Component\HttpFoundation\Request;

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

    /** @var bool */
    private $is_dir = false;

    /** @var bool */
    private $is_image = false;

    /** @var bool */
    private $is_video = false;

    /** @var bool */
    private $is_audio = false;

    /**
     * This array is used for getting the readable file type
     * with a mimetype
     * @var array
     */
    public static $humanReadableTypes = array(
        'unknown'                       => 'Unknown',
        'directory'                     => 'Folder',
        'application/pdf'               => 'PDF',
        'application/xml'               => 'XML',
        'application/x-shockwave-flash' => 'AppFlash',
        'application/flash-video'       => 'Flash video',
        'application/javascript'        => 'JS',
        'application/x-gzip'            => 'GZIP',
        'application/x-bzip2'           => 'BZIP',
        'application/zip'               => 'ZIP',
        'application/x-zip'             => 'ZIP',
        'application/x-rar'             => 'RAR',
        'application/x-tar'             => 'TAR',
        'text/plain'                    => 'TXT',
        'text/html'                     => 'HTML',
        'text/javascript'               => 'JS',
        'text/css'                      => 'CSS',
        'text/xml'                      => 'XML',
        'text/x-php'                    => 'PHP',
        'text/x-shellscript'            => 'Shell Script',
        'image/jpeg'                    => 'JPEG',
        'image/gif'                     => 'GIF',
        'image/png'                     => 'PNG',
        'audio/mpeg'                    => 'Audio MPEG',
        'audio/ogg'                     => 'Audio OGG',
        'audio/mp4'                     => 'Audio MPEG4',
        'video/mp4'                     => 'Video MPEG4',
        'video/mpeg'                    => 'Video MPEG',
        'video/ogg'                     => 'Video OGG',
        'application/ogg'               => 'Video OGG',
        'inode/x-empty'                 => 'Text'
    );

    /**
     * @param       $filepath
     * @param Media $media
     */
    public function __construct($filepath, Media $media)
    {
        $filename = basename($filepath);

        $file_size = $this->calculateReadableSize(filesize($filepath));
        $file_modification = date("Y-m-d H:i:s", filemtime($filepath));
        $mimetype = mime_content_type($filepath);
        $web_path = $media->DirTrim($media->getPath($media->getDirPath()), $filename, true);

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
     * @param string $mimetype
     */
    public function setFileclass($mimetype)
    {
        switch ($mimetype) {
            case 'directory':
                $fileclass = "dir";
                $this->is_dir = true;
                break;
            case 'application/pdf':
                $fileclass = "pdf";
                break;
            case 'application/zip':
            case 'application/x-gzip':
            case 'application/x-bzip2':
            case 'application/x-zip':
            case 'application/x-rar':
            case 'application/x-tar':
                $fileclass = "zip";
                break;
            case 'video/mp4':
            case 'video/ogg':
            case 'video/mpeg':
            case 'application/ogg':
                $fileclass = "video";
                $this->is_video = true;
                break;
            case 'audio/ogg':
            case 'audio/mpeg':
                $fileclass = "audio";
                $this->is_audio = true;
                break;
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/gif':
            case 'image/png':
                $fileclass = "image";
                $this->is_image = true;
                break;
            case 'image/svg+xml':
                $fileclass = "svg";
                $this->is_image = true;
                break;
            case 'text/x-shellscript':
                $fileclass = 'shellscript';
                break;
            case 'text/html':
            case 'text/javascript':
            case 'text/css':
            case 'text/xml':
            case 'application/javascript':
            case 'application/xml':
                $fileclass = "code";
                break;
            case 'text/x-php':
                $fileclass = "php";
                break;
            case 'application/x-shockwave-flash':
                $fileclass = 'swf';
                break;
            default:
                $fileclass = "default";
                break;
        }
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

        if (isset(self::$humanReadableTypes[$mimetype])) {
            $this->setReadableType(self::$humanReadableTypes[$mimetype]);
        } else {
            $this->setReadableType("Undefined");
        }
        $this->setFileclass($mimetype);
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
        return $this->is_dir;
    }

    /**
     * @return boolean
     */
    public function isImage()
    {
        return $this->is_image;
    }

    /**
     * @return boolean
     */
    public function isVideo()
    {
        return $this->is_video;
    }

    /**
     * @return boolean
     */
    public function isAudio()
    {
        return $this->is_audio;
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


    /**
     * This function returns the file size in a readable way for the user.
     *
     * @param int $bytes
     * @param int $decimals
     * @return string - returns the file size in Bytes, Kilobytes, Megabytes or Gigabytes.
     */
    public function calculateReadableSize($bytes, $decimals = 2)
    {
        $sz = array('B', 'KB', 'MB', 'GB');
        $factor = (int) floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . " " . @$sz[$factor];
    }
}