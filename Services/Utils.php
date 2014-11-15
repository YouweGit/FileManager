<?php

namespace Youwe\MediaBundle\Services;

/**
 * Class Utils
 * @package Youwe\MediaBundle\Services
 */
class Utils
{

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
     * A array that contains the possible mimetypes for uploading
     * @var array
     */
    public static $mimetypes = array(
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
     * @author Jim Ouwerkerk
     * @param string $path
     * @param string $file
     * @param bool $rTrim
     * @return string
     */
    public static function DirTrim($path, $file = null, $rTrim = false){
        if ($rTrim) {
            $result = rtrim($path, DIRECTORY_SEPARATOR);
        } else {
            $result = trim($path, DIRECTORY_SEPARATOR);
        }

        if(!is_null($file)){
            $file_result = trim($file, DIRECTORY_SEPARATOR);
        } else {
            $file_result = $file;
        }

        if(!is_null($file_result)){
            $result = $result .DIRECTORY_SEPARATOR. $file_result;
        }

        return $result;
    }

    /**
     * @param int    $bytes
     * @param int    $decimals
     * @return string
     */
    public static function readableSize($bytes, $decimals = 2)
    {
        $sz = array('B', 'KB', 'MB', 'GB');
        $factor = (int) floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . " " . @$sz[$factor];
    }


    /**
     * @param $mimetype
     * @return string
     */
    public static function getFileClass($mimetype)
    {
        switch ($mimetype) {
            case 'directory':
                $fileclass = "dir";
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
                break;
            case 'audio/ogg':
            case 'audio/mpeg':
                $fileclass = "audio";
                break;
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/gif':
            case 'image/png':
                $fileclass = "image";
                break;
            case 'image/svg+xml':
                $fileclass = "svg";
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

        return $fileclass;
    }
}
