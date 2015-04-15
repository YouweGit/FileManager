<?php

namespace Youwe\FileManagerBundle\Model;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileInfo
 * @package Youwe\FileManagerBundle\Model
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

    /** @var FileManager */
    private $file_manager;

    /**
     * This array is used for getting the readable file type
     * with a mimetype
     *
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

    public static $mimetypes_extensions = array(
        // applications
        'pdf'  => 'application/pdf',
        'swf'  => 'application/x-shockwave-flash',
        // open office (finfo detect as application/zip)
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ott'  => 'application/vnd.oasis.opendocument.text-template',
        'oth'  => 'application/vnd.oasis.opendocument.text-web',
        'odm'  => 'application/vnd.oasis.opendocument.text-master',
        'odg'  => 'application/vnd.oasis.opendocument.graphics',
        'otg'  => 'application/vnd.oasis.opendocument.graphics-template',
        'odp'  => 'application/vnd.oasis.opendocument.presentation',
        'otp'  => 'application/vnd.oasis.opendocument.presentation-template',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        'ots'  => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'odc'  => 'application/vnd.oasis.opendocument.chart',
        'odf'  => 'application/vnd.oasis.opendocument.formula',
        'odb'  => 'application/vnd.oasis.opendocument.database',
        'odi'  => 'application/vnd.oasis.opendocument.image',
        'oxt'  => 'application/vnd.openofficeorg.extension',
        // MS office 2007 (finfo detect as application/zip)
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        // archives
        'gz'   => 'application/x-gzip',
        'tgz'  => 'application/x-gzip',
        'bz'   => 'application/x-bzip2',
        'bz2'  => 'application/x-bzip2',
        'tbz'  => 'application/x-bzip2',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar',
        'tar'  => 'application/x-tar',
        '7z'   => 'application/x-7z-compressed',
        // texts
        'txt'  => 'text/plain',
        'php'  => 'text/x-php',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'js'   => 'text/javascript',
        'css'  => 'text/css',
        'rtf'  => 'text/rtf',
        'rtfd' => 'text/rtfd',
        'py'   => 'text/x-python',
        'java' => 'text/x-java-source',
        'rb'   => 'text/x-ruby',
        'sh'   => 'text/x-shellscript',
        'pl'   => 'text/x-perl',
        'xml'  => 'text/xml',
        'sql'  => 'text/x-sql',
        'c'    => 'text/x-csrc',
        'h'    => 'text/x-chdr',
        'cpp'  => 'text/x-c++src',
        'hh'   => 'text/x-c++hdr',
        'log'  => 'text/plain',
        'csv'  => 'text/x-comma-separated-values',
        // images
        'bmp'  => 'image/x-ms-bmp',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'tga'  => 'image/x-targa',
        'psd'  => 'image/vnd.adobe.photoshop',
        'ai'   => 'image/vnd.adobe.photoshop',
        'xbm'  => 'image/xbm',
        'pxm'  => 'image/pxm',
        //audio
        'mp3'  => 'audio/mpeg',
        'mid'  => 'audio/midi',
        'ogg'  => 'audio/ogg',
        'oga'  => 'audio/ogg',
        'm4a'  => 'audio/x-m4a',
        'wav'  => 'audio/wav',
        'wma'  => 'audio/x-ms-wma',
        // video
        'avi'  => 'video/x-msvideo',
        'dv'   => 'video/x-dv',
        'mp4'  => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'mpg'  => 'video/mpeg',
        'mov'  => 'video/quicktime',
        'wm'   => 'video/x-ms-wmv',
        'flv'  => 'video/x-flv',
        'mkv'  => 'video/x-matroska',
        'webm' => 'video/webm',
        'ogv'  => 'video/ogg',
        'ogm'  => 'video/ogg'
    );

    /**
     * Constructor
     *
     * Set all known file properties to the class
     *
     * @param       $filepath - Filepath with file name
     * @param FileManager $file_manager
     */
    public function __construct($filepath, FileManager $file_manager)
    {
        $filename = basename($filepath);

        $file_size = $this->calculateReadableSize(filesize($filepath));
        $file_modification = date("Y-m-d H:i:s", filemtime($filepath));
        $this->setFileManager($file_manager);
        $web_path = $file_manager->DirTrim($file_manager->getPath($file_manager->getDirPath()), $filename, true);

        $this->setFilename($filename);
        $this->guessMimeType($filepath);
        $this->setModified($file_modification);
        $this->setFileSize($file_size);
        $this->setFilepath(dirname($filepath));
        $this->setWebPath($web_path);
        $this->setUsages($file_manager);
    }

    /**
     * Returns the fileclass
     *
     * Javascript uses the fileclass to check which actions the file should have.
     * CSS uses the file class to check which icon belongs to the file.
     *
     * @return string
     */
    public function getFileclass()
    {
        return $this->fileclass;
    }

    /**
     * Set the file class
     *
     * Javascript uses the fileclass to check which actions the file should have.
     * CSS uses the file class to check which icon belongs to the file.
     *
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
     * Check if the mimetype match with the extension
     *
     * @param $mimetype
     * @param $extension
     * @return bool
     */
    public function matchMimetypeExtension($mimetype, $extension) {
        $mime_extensions = self::$mimetypes_extensions;
        if(isset($mime_extensions[$extension]) && $mime_extensions[$extension] == $mimetype){
            return true;
        }

        return false;
    }

    /**
     * Returns the file name
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set the filename
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Returns the full path to the file
     *
     * @param bool $include_filename
     * @return string
     */
    public function getFilepath($include_filename = false)
    {
        $filepath = $this->filepath;
        if($include_filename){
            $filepath .= FileManager::DS . $this->getFIlename();
        }
        return $filepath;
    }

    /**
     * Set the full path to the file
     *
     * @param string $filepath
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * Returns the mimetype of the file
     *
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * Sets the mimetype of the file
     *
     * @param string $mimetype
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    /**
     * Try to guess the mimetypes first with a custom magic file.
     * This fix the problems with wrong mime type detection.
     *
     * @param $filepath
     * @return mixed
     */
    public function guessMimeType($filepath){
        $magic_file = $this->getFileManager()->getMagicFile();
        $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE, $magic_file), $filepath);

        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $match_extension = $this->matchMimetypeExtension($mimetype, $extension);

        //Use default mimetype guesser if it is not found
        if($mimetype == "application/octet-stream" || !$match_extension){
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filepath);
        }

        $this->setMimetype($mimetype);

        if (isset(self::$humanReadableTypes[$mimetype])) {
            $this->setReadableType(self::$humanReadableTypes[$mimetype]);
        } else {
            $this->setReadableType("Undefined");
        }
        $this->setFileclass($mimetype);
    }

    /**
     * Returns the modified datetime
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set the modified datetime
     *
     * @param string $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * Returns the readable type for users of the file
     *
     * @return string
     */
    public function getReadableType()
    {
        return $this->readableType;
    }

    /**
     * Sets the readable mime type
     *
     * @param string $readableType
     */
    public function setReadableType($readableType)
    {
        $this->readableType = $readableType;
    }

    /**
     * Returns the size of the file
     *
     * @return string
     */
    public function getFileSize()
    {
        return $this->file_size;
    }

    /**
     * Set the size of the file
     *
     * @param string $file_size
     */
    public function setFileSize($file_size)
    {
        $this->file_size = $file_size;
    }

    /**
     * Returns the locations where the file is used.
     *
     * @return int
     */
    public function getUsages()
    {
        return $this->usages;
    }

    /**
     * Set the locations where the file is used in an array.
     *
     * This function requires a class that is set in the config with the function returnUsages.
     *
     * @param FileManager $file_manager
     */
    public function setUsages(FileManager $file_manager)
    {
        $usages = array();
        $usage_class = $file_manager->getUsagesClass();
        if ($usage_class != false) {
            /** @var mixed $usage_object */
            $usage_object = new $usage_class;
            $usages_result = $usage_object->returnUsages($this->getFilepath(true));
            if (!empty($usages_result)) {
                $usages = $usages_result;
            }
        }
        $this->usages = count($usages);
    }

    /**
     * Returns the web path of the file
     *
     * @param bool $trim
     * @return string
     */
    public function getWebPath($trim = false)
    {
        if($trim) {
            return trim($this->web_path, FileManager::DS);
        } else {
            return $this->web_path;
        }
    }

    /**
     * Set the web path of the file
     *
     * @param string $web_path
     */
    public function setWebPath($web_path)
    {
        $this->web_path = $web_path;
    }

    /**
     * Check if the file is a directory
     *
     * @return bool
     */
    public function isDir()
    {
        return $this->is_dir;
    }

    /**
     * Check if the file is a image
     *
     * @return boolean
     */
    public function isImage()
    {
        return $this->is_image;
    }

    /**
     * Check if the file is a video
     *
     * @return boolean
     */
    public function isVideo()
    {
        return $this->is_video;
    }

    /**
     * Check if the file is a audio
     *
     * @return boolean
     */
    public function isAudio()
    {
        return $this->is_audio;
    }

    /**
     * Return all information in an array
     *
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

    /**
     * Returns the file manager object
     *
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->file_manager;
    }

    /**
     * Sets the file manager object
     *
     * @param FileManager $file_manager
     */
    public function setFileManager($file_manager)
    {
        $this->file_manager = $file_manager;
    }
}