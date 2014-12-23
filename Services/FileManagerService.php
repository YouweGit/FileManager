<?php

namespace Youwe\FileManagerBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Youwe\FileManagerBundle\Driver\FileManagerDriver;
use Youwe\FileManagerBundle\Model\FileInfo;
use Youwe\FileManagerBundle\Model\FileManager;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerService
 * @package Youwe\FileManagerBundle\Services
 */
class FileManagerService
{
    /** @var  FileManager */
    private $file_manager;

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
     * @return FileManager
     */
    public function createFileManager($parameters, $driver, $dir_path = null)
    {
        $file_manager = new FileManager($parameters, $driver, $this->container);
        $this->file_manager = $file_manager;
        $file_manager->setDirPaths($dir_path);
        $this->setDisplayType();
        return $file_manager;
    }

    /**
     * @param FileManager $file_manager
     * @throws \Exception
     * @return bool|string
     */
    public function getFilePath(FileManager $file_manager)
    {
        if (($file_manager->getFilename() == "" && !is_null($file_manager->getTargetFilepath()))) {
            throw new \Exception("Filename cannot be empty when there is a target file");
        }

        $root = $file_manager->getUploadPath();
        $dir_path = $file_manager->getDirPath();

        if (empty($dir_path)) {
            $dir = $root;
        } else {
            if (strcasecmp("../", $dir_path) >= 1) {
                throw new \Exception("Invalid filepath or filename");
            }
            $dir = $this->getFileManager()->DirTrim($root, $dir_path, true);
        }

        try {
            $file_manager->checkPath($dir);
            if (!is_null($file_manager->getTargetFilepath())) {
                $file_manager->checkPath($file_manager->getTargetFilepath());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $dir;
    }

    /**
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->file_manager;
    }

    /**
     * @param $token
     * @throws \Exception
     */
    public function checkToken($token)
    {
        $valid = $this->container->get('form.csrf_provider')->isCsrfTokenValid('file_manager', $token);

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
        $file_manager = $this->getFileManager();
        if ('POST' === $this->container->get('request')->getMethod()) {
            $form->handleRequest($this->container->get('request'));
            if ($form->isValid()) {
                $file_manager->checkPath();

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
        $dir = $this->getFileManager()->getDir();

        /** @var FileManagerDriver $driver */
        $driver = $this->container->get('youwe.file_manager.driver');

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }

            if (in_array($file->getClientMimeType(), $this->getFileManager()->getExtensionsAllowed())) {
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

        $this->getFileManager()->getDriver()->makeDir($new_dir);
    }

    /**
     * @param Form $form
     * @throws \Exception
     */
    private function handleRenameFile($form)
    {
        $dir = $this->getFileManager()->getDir();
        /** @var FileManagerDriver $driver */
        $driver = $this->container->get('youwe.file_manager.driver');

        $new_file = $form->get('rename_file')->getData();
        $new_file = str_replace("../", "", $new_file);

        $org_filename = $form->get('origin_file_name')->getData();
        $path = $this->getFileManager()->DirTrim($dir, $org_filename);
        $org_extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($org_extension != "") {
            $new_filename = $new_file . "." . $org_extension;
        } else {
            $path = $this->getFileManager()->DirTrim($dir, $org_filename, true);
            if (is_dir($path)) {
                $new_filename = $new_file;
            } else {
                throw new \Exception("Extension is empty", 500);
            }
        }
        $fileInfo = new FileInfo($this->getFileManager()->DirTrim($dir, $org_filename, true), $this->getFileManager());
        $driver->renameFile($fileInfo, $new_filename);
    }

    /**
     * @param Form $form
     * @internal param FileManager $settings
     * @return array
     */
    public function getRenderOptions(Form $form)
    {
        $file_manager = $this->getFileManager();
        $dir_files = scandir($file_manager->getDir());
        $root_dirs = scandir($file_manager->getUploadPath());

        $options['files'] = $this->getFiles($dir_files);
        $options['file_body_display'] = $file_manager->getDisplayType();
        $options['dirs'] = $this->getDirectoryTree($root_dirs, $file_manager->getUploadPath(), "");
        $options['isPopup'] = $this->container->get('request')->get('popup');
        $options['copy_file'] = $this->container->get('session')->get('copy');
        $options['form'] = $form->createView();

        $options['root_folder'] = $file_manager->getPath();
        $options['current_path'] = $file_manager->getDirPath();
        $options['upload_allow'] = $file_manager->getExtensionsAllowed();
        $options['usages'] = $file_manager->getUsagesClass();
        $options['theme_css'] = $file_manager->getThemeCss();

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
            $filepath = $this->getFileManager()->DirTrim($this->getFileManager()->getDir(), $file, true);

            //Only show non-hidden files
            if ($file[0] != ".") {
                $files[] = new FileInfo($filepath, $this->getFileManager());
            }
        }
        return $files;
    }

    /**
     * @return string
     */
    public function setDisplayType()
    {
        $file_manager = $this->getFileManager();
        /** @var Session $session */
        $session = $this->container->get('session');
        $display_type = $session->get('display_file_manager_type');

        if ($this->container->get('request')->get('display_type') != null) {
            $display_type = $this->container->get('request')->get('display_type');
            $session->set('display_file_manager_type', $display_type);
        } else {
            if (is_null($display_type)) {
                $display_type = "file_body_block";
            }
        }

        $file_body_display = $display_type !== null ? $display_type : "file_body_block";

        $file_manager->setDisplayType($file_body_display);
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
            $filepath = $this->getFileManager()->DirTrim($dir, $file, true);
            if ($file[0] != ".") {
                if (is_dir($filepath)) {
                    $new_dir_files = scandir($filepath);
                    $new_dir_path = $this->getFileManager()->DirTrim($dir_path, $file, true);
                    $new_dir = $this->getFileManager()->getUploadPath() . FileManager::DS . $this->getFileManager()->DirTrim($new_dir_path);
                    $fileType = "directory";
                    $tmp_array = array(
                        "mimetype" => $fileType,
                        "name"     => $file,
                        "path"     => $this->getFileManager()->DirTrim($new_dir_path),
                        "tree"     => $this->getDirectoryTree($new_dir_files, $new_dir, $new_dir_path, array()),
                    );
                    $dirs[] = $tmp_array;
                }
            }
        }
        return $dirs;
    }
}
