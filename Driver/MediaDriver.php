<?php

namespace Youwe\MediaBundle\Driver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class MediaDriver
 * @package Youwe\MediaBundle\Driver
 */
class MediaDriver
{
    /**
     * Contains all mime types that are allowed to upload.
     * @var array
     */
    public $mime_allowed;

    /**
     * The path to the upload directory
     * @var string
     */
    public $upload_path;

    /**
     * The class where the function is defined for getting
     * the usages amount of the media item
     *
     * This class should have the function returnUsage();
     * @var
     */
    public $usage_class;

    /**
     * This array is used for getting the readable file type
     * with a mimetype
     * @var array
     */
    protected static $humanReadableTypes = array(
        'unknown' => 'Unknown',
        'directory' => 'Folder',
        'application/pdf' => 'PDF',
        'application/xml' => 'XML',
        'application/x-shockwave-flash' => 'AppFlash',
        'application/flash-video' => 'Flash video',
        'application/javascript' => 'JS',
        'application/x-gzip' => 'GZIP',
        'application/x-bzip2' => 'BZIP',
        'application/zip' => 'ZIP',
        'application/x-zip' => 'ZIP',
        'application/x-rar' => 'RAR',
        'application/x-tar' => 'TAR',
        'text/plain' => 'TXT',
        'text/html' => 'HTML',
        'text/javascript' => 'JS',
        'text/css' => 'CSS',
        'text/xml' => 'XML',
        'text/x-shellscript' => 'Shell Script',
        'image/jpeg' => 'JPEG',
        'image/gif' => 'GIF',
        'image/png' => 'PNG',
        'audio/mpeg' => 'Audio MPEG',
        'audio/ogg' => 'Audio OGG',
        'audio/mp4' => 'Audio MPEG4',
        'video/mp4' => 'Video MPEG4',
        'video/mpeg' => 'Video MPEG',
        'video/ogg' => 'Video OGG',
    );

    /**
     * A array that contains the possible mimetypes for uploading
     * @var array
     */
    protected static $mimetypes = array(
        // applications
        'pdf'   => 'application/pdf',
        'swf'   => 'application/x-shockwave-flash',
        // open office (finfo detect as application/zip)
        'odt'   => 'application/vnd.oasis.opendocument.text',
        'ott'   => 'application/vnd.oasis.opendocument.text-template',
        'oth'   => 'application/vnd.oasis.opendocument.text-web',
        'odm'   => 'application/vnd.oasis.opendocument.text-master',
        'odg'   => 'application/vnd.oasis.opendocument.graphics',
        'otg'   => 'application/vnd.oasis.opendocument.graphics-template',
        'odp'   => 'application/vnd.oasis.opendocument.presentation',
        'otp'   => 'application/vnd.oasis.opendocument.presentation-template',
        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        'ots'   => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'odc'   => 'application/vnd.oasis.opendocument.chart',
        'odf'   => 'application/vnd.oasis.opendocument.formula',
        'odb'   => 'application/vnd.oasis.opendocument.database',
        'odi'   => 'application/vnd.oasis.opendocument.image',
        'oxt'   => 'application/vnd.openofficeorg.extension',
        // MS office 2007 (finfo detect as application/zip)
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'docm'  => 'application/vnd.ms-word.document.macroEnabled.12',
        'dotx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'dotm'  => 'application/vnd.ms-word.template.macroEnabled.12',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        'xltx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        'xltm'  => 'application/vnd.ms-excel.template.macroEnabled.12',
        'xlsb'  => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        'xlam'  => 'application/vnd.ms-excel.addin.macroEnabled.12',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pptm'  => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        'ppsx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppsm'  => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        'potx'  => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        'potm'  => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        'ppam'  => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        'sldx'  => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
        'sldm'  => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
        // archives
        'gz'    => 'application/x-gzip',
        'tgz'   => 'application/x-gzip',
        'bz'    => 'application/x-bzip2',
        'bz2'   => 'application/x-bzip2',
        'tbz'   => 'application/x-bzip2',
        'zip'   => 'application/zip',
        'rar'   => 'application/x-rar',
        'tar'   => 'application/x-tar',
        '7z'    => 'application/x-7z-compressed',
        // texts
        'txt'   => 'text/plain',
        'php'   => 'text/x-php',
        'html'  => 'text/html',
        'htm'   => 'text/html',
        'js'    => 'text/javascript',
        'css'   => 'text/css',
        'rtf'   => 'text/rtf',
        'rtfd'  => 'text/rtfd',
        'py'    => 'text/x-python',
        'java'  => 'text/x-java-source',
        'rb'    => 'text/x-ruby',
        'sh'    => 'text/x-shellscript',
        'pl'    => 'text/x-perl',
        'xml'   => 'text/xml',
        'sql'   => 'text/x-sql',
        'c'     => 'text/x-csrc',
        'h'     => 'text/x-chdr',
        'cpp'   => 'text/x-c++src',
        'hh'    => 'text/x-c++hdr',
        'log'   => 'text/plain',
        'csv'   => 'text/x-comma-separated-values',
        // images
        'bmp'   => 'image/x-ms-bmp',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png',
        'tif'   => 'image/tiff',
        'tiff'  => 'image/tiff',
        'tga'   => 'image/x-targa',
        'psd'   => 'image/vnd.adobe.photoshop',
        'ai'    => 'image/vnd.adobe.photoshop',
        'xbm'   => 'image/xbm',
        'pxm'   => 'image/pxm',
        //audio
        'mp3'   => 'audio/mpeg',
        'mid'   => 'audio/midi',
        'ogg'   => 'audio/ogg',
        'oga'   => 'audio/ogg',
        'm4a'   => 'audio/x-m4a',
        'wav'   => 'audio/wav',
        'wma'   => 'audio/x-ms-wma',
        // video
        'avi'   => 'video/x-msvideo',
        'dv'    => 'video/x-dv',
        'mp4'   => 'video/mp4',
        'mpeg'  => 'video/mpeg',
        'mpg'   => 'video/mpeg',
        'mov'   => 'video/quicktime',
        'wm'    => 'video/x-ms-wmv',
        'flv'   => 'video/x-flv',
        'mkv'   => 'video/x-matroska',
        'webm'  => 'video/webm',
        'ogv'   => 'video/ogg',
        'ogm'   => 'video/ogg'
    );

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $parameters = $this->container->getParameter('youwe_media');
        $this->upload_path = $parameters['upload_path'];
        $this->mime_allowed = $parameters['mime_allowed'];
    }


    /**
     * @param $files
     * @param $dir
     * @return string
     * @throws \Exception
     */
    public function handleFiles($files, $dir){
        /** @var UploadedFile $file */
        foreach($files as $file){

            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }

            if(in_array($file->getClientMimeType(), $this->mime_allowed)){
                $original_file = $file->getClientOriginalName();
                $path_parts = pathinfo($original_file);

                $increment = '';
                while(file_exists($dir . "/" . $path_parts['filename'] . $increment . '.' . $extension)) {
                    $increment++;
                }

                $basename = $path_parts['filename'] . $increment . '.' . $extension;
                $file->move($dir,$basename);
            } else {
                throw new \Exception("Mimetype is not allowed", 500);
            }
        }
        return true;
    }

    /**
     * @param $dir
     * @param $dir_name
     * @throws \Exception
     * @return bool
     */
    public function makeDir($dir, $dir_name){
        $fm = new Filesystem();
        $dir_path = rtrim($dir,"/") . "/" . $dir_name;
        if(!file_exists($dir_path)){
            $fm->mkdir($dir_path, 0700);
        } else {
            throw new \Exception("Cannot create directory '" . $dir_name ."': Directory already exists", 500);
        }
    }

    /**
     * @param $dir
     * @param $file_name
     * @param $new_file_name
     * @return bool
     */
    public function renameFile($dir, $file_name, $new_file_name){
        $fm = new Filesystem();
        $old_file = rtrim($dir,"/") . "/" . $file_name;
        $new_file = rtrim($dir,"/") . "/" . $new_file_name;
        $fm->rename($old_file, $new_file);
    }

    /**
     * @param $dir
     * @param $file_name
     * @param $new_file_name
     * @return bool
     */
    public function moveFile($dir, $file_name, $new_file_name){
        $file = rtrim($dir,"/") . "/" . $file_name;
        $fm = new File($file, false);
        $fm->move($new_file_name);
    }

    /**
     * @param $dir
     * @param $file_name
     * @return bool
     */
    public function deleteFile($dir, $file_name){
        $fm = new Filesystem();
        $file = rtrim($dir,"/") . "/" . $file_name;
        $fm->remove($file);
    }

    /**
     * @param $dir
     * @param $zip_file
     * @throws \Exception
     * @return bool
     */
    public function extractZip($dir, $zip_file){
        $chapterZip = new \ZipArchive ();
        $tmp_dir = $this->upload_path . "/" . "." . strtotime("now");
        $fm = new Filesystem();
        $fm->mkdir($tmp_dir);

        if ($chapterZip->open ( $dir . "/" . $zip_file )) {
            $chapterZip->extractTo($tmp_dir);
            $chapterZip->close();
        }
        $di = new \RecursiveDirectoryIterator($tmp_dir);
        foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
            $mime_valid = $this->checkMimeType($filename);
            if($mime_valid !== true){
                $fm->remove($tmp_dir);
                throw new \Exception($mime_valid, 500);
            }
        }
        $fm->remove($tmp_dir);
        if ($chapterZip->open ( $dir . "/" . $zip_file )) {

            $chapterZip->extractTo($dir);
            $chapterZip->close();
        }
    }

    /**
     * @param $name
     * @return false
     */
    public function checkMimeType($name){
        $mime = mime_content_type($name);

        if($mime !=  'directory'){
            if (!in_array($mime, $this->mime_allowed)) {
                return 'Mime type "'.mime_content_type($name).'" not allowed for file "'. basename($name) .'"';
            }
            return true;
        }
        else {
            return true;
        }
    }
}
