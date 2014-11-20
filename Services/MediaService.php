<?php

namespace Youwe\MediaBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;
use Youwe\MediaBundle\Driver\MediaDriver;
use Youwe\MediaBundle\Model\FileInfo;
use Youwe\MediaBundle\Model\Media;

/**
 * Class MediaService
 * @package Youwe\MediaBundle\Services
 */
class MediaService
{
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


    /** @var  Media */
    private $media;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @author Jim Ouwerkerk
     * @param $parameters
     * @param $dir_path
     * @param $driver
     * @return Media
     */
    public function createMedia($parameters, $driver, $dir_path = null)
    {
        $media = new Media($parameters, $driver);
        $this->media = $media;
        $media->setDirPaths($this, $dir_path);
        return $media;
    }

    /**
     * @param Media $media
     * @throws \Exception
     * @return bool|string
     */
    public function getFilePath(Media $media)
    {
        if (($media->getFilename() == "" && !is_null($media->getTargetFilepath()))) {
            throw new \Exception("Filename cannot be empty when there is a target file");
        }

        $root = $media->getUploadPath();
        $dir_path = $media->getDirPath();

        if (empty($dir_path)) {
            $dir = $root;
        } else {
            if (strcasecmp("../", $dir_path) >= 1) {
                throw new \Exception("Invalid filepath or filename");
            }
            $dir = $this->getMedia()->DirTrim($root, $dir_path, true);
        }

        try {
            $this->checkPath($dir);
            if (!is_null($media->getTargetFilepath())) {
                $this->checkPath($media->getTargetFilepath());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $dir;
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

        /* /web/uploads/page */
        $realpath = realpath($path);

        /* /web/uploads */
        $upload_path = realpath($this->getMedia()->getUploadPath());

        //If this path is higher than the parent folder
        if (strcasecmp($realpath, $upload_path > 0)) {
            return true;
        } else {
            throw new \Exception("Directory is not in the upload path", 403);
        }
    }

    /**
     * @author Jim Ouwerkerk
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
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
     * @param $pathInfo
     * @return string
     */
    public function getMimeType($pathInfo)
    {
        $types = self::$mimetypes;

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
     * @throws \Exception
     * @return null|string
     */
    public function handleFormSubmit(Form $form)
    {
        $media = $this->getMedia();
        $dir = $media->getDir();
        if ('POST' === $this->container->get('request')->getMethod()) {
            $form->handleRequest($this->container->get('request'));
            if ($form->isValid()) {
                $this->checkPath($dir);

                $files = $form->get("file")->getData();
                if (!is_null($files)) {
                    $this->handleUploadFiles($files);
                } elseif (!is_null($form->get("newfolder")->getData())) {
                    $this->handleNewDir($form);
                } elseif (!is_null($form->get('rename_file')->getData())) {
                    $this->handleRenameFile($form);
                } else {
                    throw new \Exception("Undefined action", 500);
                }
            } else {
                throw new \Exception($form->getErrorsAsString(), 500);
            }
        }
    }

    /**
     * @author Jim Ouwerkerk
     * @param array $files
     * @return bool
     */
    private function handleUploadFiles($files)
    {
        $dir = $this->getMedia()->getDir();

        /** @var MediaDriver $driver */
        $driver = $this->container->get('youwe.media.driver');

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }

            if (in_array($file->getClientMimeType(), $this->getMedia()->getExtensionsAllowed())) {
                $driver->handleUploadedFiles($file, $extension, $dir);
            } else {
                $driver->throwError("Mimetype is not allowed", 500);
            }
        }
    }

    /**
     * @author Jim Ouwerkerk
     * @param Form $form
     */
    private function handleNewDir($form)
    {
        $new_dir = $form->get("newfolder")->getData();
        $new_dir = str_replace("../", "", $new_dir);

        $this->getMedia()->getDriver()->makeDir($new_dir);
    }

    /**
     * @author Jim Ouwerkerk
     * @param Form $form
     * @throws \Exception
     */
    private function handleRenameFile($form)
    {
        $dir = $this->getMedia()->getDir();
        /** @var MediaDriver $driver */
        $driver = $this->container->get('youwe.media.driver');

        $new_file = $form->get('rename_file')->getData();
        $new_file = str_replace("../", "", $new_file);

        $org_filename = $form->get('origin_file_name')->getData();
        $path = $this->getMedia()->DirTrim($dir, $org_filename);
        $org_extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($org_extension != "") {
            $new_filename = $new_file . "." . $org_extension;
        } else {
            $path = $this->getMedia()->DirTrim($dir, $org_filename, true);
            if (is_dir($path)) {
                $new_filename = $new_file;
            } else {
                throw new \Exception("Extension is empty", 500);
            }
        }
        $fileInfo = new FileInfo($this->getMedia()->DirTrim($dir, $org_filename, true), $this->getMedia());
        $driver->renameFile($fileInfo, $new_filename);
    }

    /**
     * @author   Jim Ouwerkerk
     * @param Form $form
     * @internal param Media $settings
     * @return array
     */
    public function getRenderOptions(Form $form)
    {
        $media = $this->getMedia();
        $dir_files = scandir($media->getDir());
        $root_dirs = scandir($media->getUploadPath());

        $options['files'] = $this->getFiles($dir_files);
        $options['file_body_display'] = $this->getDisplayType();
        $options['root_folder'] = $media->getPath();
        $options['dirs'] = $this->getDirectoryTree($root_dirs, $media->getUploadPath(), "");
        $options['isPopup'] = $this->container->get('request')->get('popup');
        $options['copy_file'] = $this->container->get('session')->get('copy');
        $options['current_path'] = $media->getDirPath();
        $options['form'] = $form->createView();
        $options['upload_allow'] = $media->getExtensionsAllowed();
        $options['extended_template'] = $media->getExtendedTemplate();
        $options['usages'] = $media->getUsagesClass();

        return $options;
    }

    /**
     * @param array $dir_files - Files
     * @param array $files
     * @internal param bool $return_files - If false, only show dirs
     * @return array
     */
    public function getFiles(array $dir_files, $files = array())
    {
        foreach ($dir_files as $file) {
            $filepath = $this->getMedia()->DirTrim($this->getMedia()->getDir(), $file, true);

            //Only show non-hidden files
            if ($file[0] != ".") {
                $files[] = new FileInfo($filepath, $this->getMedia());
            }
        }
        return $files;
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
     * @param       $dir_files - Files
     * @param       $dir       - Current Directory
     * @param       $dir_path  - Current Directory Path
     * @param array $dirs      - Directories
     * @return array|null
     */
    public function getDirectoryTree($dir_files, $dir, $dir_path, $dirs = array())
    {
        foreach ($dir_files as $file) {
            $filepath = $this->getMedia()->DirTrim($dir, $file, true);
            if ($file[0] != ".") {
                if (is_dir($filepath)) {
                    $new_dir_files = scandir($filepath);
                    $new_dir_path = $this->getMedia()->DirTrim($dir_path, $file, true);
                    $new_dir = $this->getMedia()->getUploadPath() . Media::DS . $this->getMedia()->DirTrim($new_dir_path);
                    $fileType = "directory";
                    $tmp_array = array(
                        "mimetype" => $fileType,
                        "name"     => $file,
                        "path"     => $this->getMedia()->DirTrim($new_dir_path),
                        "tree"     => $this->getDirectoryTree($new_dir_files, $new_dir, $new_dir_path, array()),
                    );
                    $dirs[] = $tmp_array;
                }
            }
        }
        return $dirs;
    }
}
