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
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class MediaService
 * @package Youwe\MediaBundle\Services
 */
class MediaService
{
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
     * @param $parameters
     * @param $dir_path
     * @param $driver
     * @return Media
     */
    public function createMedia($parameters, $driver, $dir_path = null)
    {
        $media = new Media($parameters, $driver);
        $this->media = $media;
        $media->setDirPaths($dir_path);
        $this->setDisplayType();
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
            $media->checkPath($dir);
            if (!is_null($media->getTargetFilepath())) {
                $media->checkPath($media->getTargetFilepath());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $dir;
    }

    /**
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
     * @param Form $form
     * @throws \Exception
     * @return null|string
     */
    public function handleFormSubmit(Form $form)
    {
        $media = $this->getMedia();
        if ('POST' === $this->container->get('request')->getMethod()) {
            $form->handleRequest($this->container->get('request'));
            if ($form->isValid()) {
                $media->checkPath();

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
     * @param Form $form
     */
    private function handleNewDir($form)
    {
        $new_dir = $form->get("newfolder")->getData();
        $new_dir = str_replace("../", "", $new_dir);

        $this->getMedia()->getDriver()->makeDir($new_dir);
    }

    /**
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
        $options['file_body_display'] = $media->getDisplayType();
        $options['dirs'] = $this->getDirectoryTree($root_dirs, $media->getUploadPath(), "");
        $options['isPopup'] = $this->container->get('request')->get('popup');
        $options['copy_file'] = $this->container->get('session')->get('copy');
        $options['form'] = $form->createView();

        $options['root_folder'] = $media->getPath();
        $options['current_path'] = $media->getDirPath();
        $options['upload_allow'] = $media->getExtensionsAllowed();
        $options['usages'] = $media->getUsagesClass();
        $options['theme_css'] = $media->getThemeCss();

        return $options;
    }

    /**
     * @param array $dir_files - Files
     * @param array $files
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
     * @return string
     */
    public function setDisplayType()
    {
        $media = $this->getMedia();
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

        $media->setDisplayType($file_body_display);
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
