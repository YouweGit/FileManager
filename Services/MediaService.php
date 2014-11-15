<?php

namespace Youwe\MediaBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;
use Youwe\MediaBundle\Driver\MediaDriver;
use Youwe\MediaBundle\Model\Media;

/**
 * Class MediaService
 * @package Youwe\MediaBundle\Services
 */
class MediaService
{

    /**
     * This array is used for getting the readable file type
     * with a mimetype
     * @var array
     */
    protected static $humanReadableTypes = array(
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
    protected static $mimetypes = array(
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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $parameters = $this->container->getParameter('youwe_media');
        $this->settings = new Media($parameters);
    }

    /**
     * @param $path
     * @throws \Exception
     * @return bool|string
     */
    public function checkPath($path)
    {

        /* /project/web/uploads/page */
        if (empty($path)) {
            throw new \Exception("Directory path is empty", 400);
        }

        /* /app/uploads/page */
        $realpath = realpath($path);

        /* /app/uploads */
        $upload_path = realpath($this->settings->getUploadPath());

        //If this path is higher than the parent folder
        if (strcasecmp($realpath, $upload_path > 0)) {
            return true;
        } else {
            throw new \Exception("Directory is not in the upload path", 403);
        }
    }

    /**
     * @author Jim Ouwerkerk
     * @return string
     */
    public function getDisplayType()
    {
        /** @var Session $session */
        $session = $this->container->get('session');
        $display_type = $session->get('display_media_type');

        if ($this->container->get('request')->get('display_type') != null) {
            $display_type = $this->container->get('request')->get('display_type');
            $session->set('display_media_type', $display_type);
        } else {
            if (is_null($display_type)) {
                $display_type = "file_body_block";
            }
        }

        $file_body_display = $display_type !== null ? $display_type : "file_body_block";

        return $file_body_display;
    }

    /**
     * @param      $dir_path
     * @param      $filename
     * @param null $target_file
     * @throws \Exception
     * @return bool|string
     */
    public function getFilePath($dir_path, $filename, $target_file = null)
    {
        if (($filename == "" && !is_null($target_file))) {
            throw new \Exception("Filename cannot be empty when there is a target path");
        }

        $root = $this->settings->getUploadPath();

        if (empty($dir_path)) {
            $dir = $root;
        } else {
            $dir_path = str_replace("../", "", $dir_path);
            $dir = $root . DIRECTORY_SEPARATOR . $dir_path;
        }

        try {
            $this->checkPath($dir);
            if (!is_null($target_file)) {
                $this->checkPath($target_file);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $dir;
    }

    /**
     * @param $token
     * @throws \Exception
     */
    public function checkToken($token)
    {
        $valid = $this->container->get('form.csrf_provider')->isCsrfTokenValid('media', $token);

        if (!$valid) {
            throw new \Exception("Invalid token", 500);
        }
    }

    /**
     * @param array  $dir_files - Files
     * @param string $dir - Current Directory
     * @param string $dir_path - Current Dir path
     * @param array  $files
     * @param bool   $show_files - If false, only show dirs
     * @return array
     */
    public function getFileTree(array $dir_files, $dir, $dir_path, $files = array(), $show_files = false)
    {
        $return_files = true;
        if ($show_files === true) {
            $return_files = false;
        }

        foreach ($dir_files as $file) {
            $filepath = Utils::DirTrim($dir, $file, true);
            if ($file[0] != ".") {

                if (is_dir($filepath)) {
                    if ($show_files === true) {
                        $return_files = true;
                    }
                    $files[] = $this->createDirArray($file, $filepath, $dir_path);
                } else {
                    $files[] = $this->createFileArray($file, $filepath, $dir_path);
                }
            }
        }
        if ($return_files) {
            return $files;
        } else {
            return null;
        }
    }

    /**
     * @param $file
     * @param $filepath
     * @param $dir_path
     * @return array
     */
    public function createFileArray($file, $filepath, $dir_path)
    {
        $file_size = Utils::readableSize(filesize($filepath));
        $file_modification = filemtime($filepath);

        $pathinfo = pathinfo($filepath);
        $filename = $pathinfo['filename'];
        $mimetype = mime_content_type($filepath);

        $usages = $this->getUsages($filename, $dir_path);
        if (isset(self::$humanReadableTypes[$mimetype])) {
            $readableType = self::$humanReadableTypes[$mimetype];
        } else {
            $readableType = "Undefined";
        }
        $web_path = Utils::DirTrim($this->settings->getWebPath($dir_path), $file, true);
        $files = array(
            "filepath"        => $web_path,
            "mimetype"        => $mimetype,
            "readableType"    => $readableType,
            "name"            => $file,
            "size"            => $file_size,
            "modified"        => date("Y-m-d H:m:s", $file_modification),
            "usages"          => count($usages),
            "usage_locations" => $usages,
            "fileClass"       => Utils::getFileClass($mimetype)
        );

        return $files;
    }

    /**
     * Return the usages locations of the file.
     * The class should always contain the returnUsages function.
     *
     * @param $filename
     * @param $dir_path
     * @return array
     */
    public function getUsages($filename, $dir_path)
    {
        $folder_array = explode(DIRECTORY_SEPARATOR, $this->settings->getUploadPath());
        $last_folder = end($folder_array);

        $trimmed_path = Utils::DirTrim($dir_path, $filename);
        $full_path = DIRECTORY_SEPARATOR . $last_folder . $trimmed_path;
        $usages = array();
        $usage_class = $this->settings->getUsagesClass();
        if ($usage_class != false) {

            /** @var mixed $usage_object */
            $usage_object = new $usage_class;
            $usages_result = $usage_object->returnUsages($full_path);
            if (!empty($usages_result)) {
                $usages = $usages_result;
            }
        }

        return $usages;
    }

    /**
     * @param $file
     * @param $filepath
     * @param $dir_path
     * @return array
     */
    public function createDirArray($file, $filepath, $dir_path)
    {
        $file_size = Utils::readableSize(filesize($filepath));
        $file_modification = filemtime($filepath);

        $new_dir_path = Utils::DirTrim($dir_path, $file, true);
        $filetype = "directory";
        $dirs = array(
            "mimetype"     => $filetype,
            "readableType" => self::$humanReadableTypes[$filetype],
            "name"         => $file,
            "size"         => $file_size,
            "path"         => trim($new_dir_path, DIRECTORY_SEPARATOR),
            "modified"     => date("Y-m-d H:m:s", $file_modification),
            "usages"       => ""
        );

        return $dirs;
    }

    /**
     * @param       $dir_files - Files
     * @param       $dir - Current Directory
     * @param       $dir_path - Current Directory Path
     * @param array $dirs - Directories
     * @param bool  $show_files
     * @return array|null
     */
    public function getDirectoryTree($dir_files, $dir, $dir_path, $dirs = array(), $show_files = false)
    {
        $return_files = true;
        if ($show_files === true) {
            $return_files = false;
        }

        foreach ($dir_files as $file) {
            $filepath = Utils::DirTrim($dir, $file, true);
            if ($file[0] != ".") {
                if (is_dir($filepath)) {
                    if ($show_files === true) {
                        $return_files = true;
                    }
                    $new_dir_files = scandir($filepath);
                    $new_dir_path = Utils::DirTrim($dir_path, $file, true);
                    $new_dir = $this->settings->getUploadPath() . DIRECTORY_SEPARATOR . Utils::DirTrim($new_dir_path);
                    $fileType = "directory";
                    $tmp_array = array(
                        "mimetype" => $fileType,
                        "name"     => $file,
                        "path"     => Utils::DirTrim($new_dir_path),
                        "tree"     => $this->getDirectoryTree($new_dir_files, $new_dir, $new_dir_path, array(), true),
                    );
                    $dirs[] = $tmp_array;
                }
            }
        }
        if ($return_files) {
            return $dirs;
        } else {
            return null;
        }
    }

    /**
     * @param $pathInfo
     * @return string
     */
    public function getMimeType($pathInfo)
    {
        $types = Utils::$mimetypes;

        $ext = $pathInfo['extension'];
        if (array_key_exists($ext, $types)) {
            $mimetype = $types[$ext];
        } else {
            $mimetype = "unknown";
        }

        return $mimetype;
    }

    /**
     * @param Form $form
     * @param      $dir
     * @throws \Exception
     * @return null|string
     */
    public function handleFormSubmit($form, $dir)
    {
        if ('POST' === $this->container->get('request')->getMethod()) {
            $form->handleRequest($this->container->get('request'));
            if ($form->isValid()) {
                $this->checkPath($dir);
                /** @var MediaDriver $driver */
                $driver = $this->container->get('youwe.media.driver');

                $files = $form->get("file")->getData();
                if (!is_null($files)) {
                    $driver->handleFiles($files, $dir);
                }
                if (!is_null($form->get("newfolder")->getData())) {
                    $new_dir = $form->get("newfolder")->getData();
                    $new_dir = str_replace("../", "", $new_dir);

                    $driver->makeDir($dir, $new_dir);
                }
                if (!is_null($form->get('rename_file')->getData())) {
                    $new_file = $form->get('rename_file')->getData();
                    $new_file = str_replace("../", "", $new_file);

                    $org_filename = $form->get('origin_file_name')->getData();
                    $path = Utils::DirTrim($dir, $org_filename);
                    $org_extension = pathinfo($path, PATHINFO_EXTENSION);

                    if ($org_extension != "") {
                        $new_filename = $new_file . "." . $org_extension;
                    } else {
                        $path = Utils::DirTrim($dir, $org_filename);
                        if (is_dir($path)) {
                            $new_filename = $new_file;
                        } else {
                            throw new \Exception("Extension is empty", 500);
                        }
                    }
                    $driver->renameFile($dir, $org_filename, $new_filename);
                }
            } else {
                throw new \Exception($form->getErrorsAsString(), 500);
            }
        }
    }

    /**
     * @author Jim Ouwerkerk
     * @param Media $settings
     * @param               $form
     * @return array
     */
    public function getRenderOptions(Media $settings, Form $form)
    {


        $dir_files = scandir($settings->getDir());
        $root_dirs = scandir($settings->getUploadPath());

        $options['files'] = $this->getFileTree($dir_files, $settings->getDir(), $settings->getDirPath());
        $options['file_body_display'] = $this->getDisplayType();
        $options['root_folder'] = $settings->getWebPath();
        $options['dirs'] = $this->getDirectoryTree($root_dirs, $settings->getUploadPath(), "");
        $options['isPopup'] = $this->container->get('request')->get('popup');
        $options['copy_file'] = $this->container->get('session')->get('copy');
        $options['current_path'] = $settings->getDirPath();
        $options['form'] = $form->createView();
        $options['upload_allow'] = $settings->getExtensionsAllowed();
        $options['extended_template'] = $settings->getExtendedTemplate();
        $options['usages'] = $settings->getUsagesClass();

        return $options;
    }

    /**
     * @author Jim Ouwerkerk
     * @param $file_path
     * @return int
     */
    public function getFileSize($file_path)
    {
        return filesize($file_path);
    }
}
