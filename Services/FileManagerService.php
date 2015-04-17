<?php

namespace Youwe\FileManagerBundle\Services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Youwe\FileManagerBundle\Driver\FileManagerDriver;
use Youwe\FileManagerBundle\Model\FileInfo;
use Youwe\FileManagerBundle\Model\FileManager;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerService
 * @package Youwe\FileManagerBundle\Services
 */
class FileManagerService
{
    const DISPLAY_TYPE_BLOCK = 'file_body_block';
    const DISPLAY_TYPE_LIST = 'file_body_list';
    const DISPLAY_TYPE_SESSION = 'display_file_manager_type';

    /** @var  FileManager */
    private $file_manager;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param FileManager $file_manager
     */
    public function setFileManager($file_manager)
    {
        $this->file_manager = $file_manager;
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
     * Create the file manager object
     *
     * @param array  $parameters
     * @param string $dir_path
     * @param mixed  $driver
     * @return FileManager
     */
    public function createFileManager(array $parameters, $driver, $dir_path = null)
    {
        $file_manager = new FileManager($parameters, $driver, $this->container);
        $this->file_manager = $file_manager;
        $file_manager->setDirPaths($dir_path);
        $this->setDisplayType();
        return $file_manager;
    }

    /**
     * Get and check the current file path
     *
     * @param FileManager $file_manager
     * @throws \Exception When the file name is empty while there is a target file or when the file is invalid
     * @return bool|string
     */
    public function getFilePath(FileManager $file_manager)
    {
        $current_file = $file_manager->getCurrentFile();
        $target_file = $file_manager->getTargetFile();

        if (($current_file->getFilename() == "" && !is_null($target_file->getFilename()))) {
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
            if (!is_null($target_file)) {
                $file_manager->checkPath($target_file->getFilepath(true));
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $dir;
    }

    /**
     * Check if the form token is valid
     *
     * @param string $request_token
     * @throws InvalidCsrfTokenException when the token is not valid
     */
    public function checkToken($request_token)
    {
        $token_manager = $this->container->get('security.csrf.token_manager');
        $token = new CsrfToken('file_manager', $request_token);
        $valid = $token_manager->isTokenValid($token);

        if (!$valid) {
            throw new InvalidCsrfTokenException();
        }
    }

    /**
     * Handle the uploaded file(s)
     *
     * @param array $files
     * @return bool
     */
    public function handleUploadFiles($files)
    {
        $dir = $this->getFileManager()->getDir();

        /** @var FileManagerDriver $driver */
        $driver = $this->container->get('youwe.file_manager.driver');

        $this->getFileManager()->event(YouweFileManagerEvents::BEFORE_FILE_UPLOADED);
        
        /** @var UploadedFile $file */
        foreach ($files as $file) {
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }

            if (in_array($file->getClientMimeType(), $this->getFileManager()->getExtensionsAllowed())) {
                $driver->handleUploadedFiles($file, $extension, $dir);
            } else {
                $this->getFileManager()->throwError("Mimetype is not allowed", 500);
            }
        }

        $this->getFileManager()->event(YouweFileManagerEvents::AFTER_FILE_UPLOADED);
    }


    /**
     * Returns an array with all the rendered options
     *
     * Current options are:
     *  files             - All files in the current directory
     *  file_body_display - The display type of the filemanager (block or list)
     *  dirs              - The directories in the upload directory
     *  isPopup           - Boolean that is true when the window is a popup
     *  copy_file         - The copied file that is in the session
     *  form              - The form object
     *  root_folder       - The root directory path
     *  current_path      - The current path
     *  upload_allow      - Array with all allowed mimetypes
     *  usages            - The usages class
     *  theme_css         - The css filepath
     *
     * @param Form $form
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
        $options['copy_type'] = FileManager::FILE_COPY;
        $options['cut_type'] = FileManager::FILE_CUT;
        $options['root_folder'] = $file_manager->getPath();
        $options['current_path'] = $file_manager->getDirPath();
        $options['upload_allow'] = $file_manager->getExtensionsAllowed();
        $options['usages'] = $file_manager->getUsagesClass();
        $options['theme_css'] = $file_manager->getThemeCss();

        return $options;
    }

    /**
     * Returns all files in the current directory
     *
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
     * Set the display type
     *
     * @return string
     */
    public function setDisplayType()
    {
        $file_manager = $this->getFileManager();
        /** @var Session $session */
        $session = $this->container->get('session');
        $display_type = $session->get(self::DISPLAY_TYPE_SESSION);

        if ($this->container->get('request')->get('display_type') != null) {
            $display_type = $this->container->get('request')->get('display_type');
            $session->set(self::DISPLAY_TYPE_SESSION, $display_type);
        } else {
            if (is_null($display_type)) {
                $display_type = self::DISPLAY_TYPE_BLOCK;
            }
        }

        $file_body_display = $display_type !== null ? $display_type : self::DISPLAY_TYPE_BLOCK;

        $file_manager->setDisplayType($file_body_display);
    }

    /**
     * Returns the directory tree of the given path. This will loop itself untill it has all directories
     *
     * @param array  $dir_files - Files
     * @param string $dir       - Current Directory
     * @param string $dir_path  - Current Directory Path
     * @param array  $dirs      - Directories
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

    /**
     * Validates the request and handles the action
     *
     * @param FileManager $fileManager
     * @param Request     $request
     * @param string      $action
     * @param bool        $check_token
     * @return Response
     */
    public function handleAction(FileManager $fileManager, Request $request, $action, $check_token)
    {
        $response = new Response();
        try{
            $dir = $this->getFilePath($fileManager);
            $fileManager->setDir($dir);
            $fileManager->checkPath();
            if($check_token) {
                $this->checkToken($request->get('token'));
            }
            switch($action){
                case FileManager::FILE_DELETE:
                    $fileManager->deleteFile();
                    break;
                case FileManager::FILE_MOVE:
                    $fileManager->moveFile();
                    break;
                case FileManager::FILE_EXTRACT:
                    $fileManager->extractZip();
                    break;
                case FileManager::FILE_RENAME:
                    $fileManager->renameFile();
                    break;
                case FileManager::FILE_NEW_DIR:
                    $fileManager->newDirectory();
                    break;
                case FileManager::FILE_COPY:
                case FileManager::FILE_CUT:
                    $this->copyFile($action);
                    break;
                case FileManager::FILE_PASTE:
                    $this->pasteFile();
                    break;
                case FileManager::FILE_INFO:
                    $response = new JsonResponse();
                    $response->setData(json_encode($fileManager->getCurrentFile()->toArray()));
                    break;
            }
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }
        return $response;
    }

    /**
     * @param string $action
     */
    public function copyFile($action){
        $sources = array(
            'display_dir' => $this->getFileManager()->getDirPath(),
            'source_dir' => $this->getFileManager()->getDir(),
            'source_file' => $this->getFileManager()->getCurrentFile()->getFilename(),
            'type' => $action
        );
        $this->container->get('session')->set('copy', $sources);
    }

    /**
     * Paste the file
     */
    public function pasteFile()
    {
        /** @var Session $session */
        $session = $this->container->get('session');
        $sources = $session->get('copy');
        $type = $sources['type'];
        $this->getFileManager()->pasteFile($type);
        $session->remove('copy');
    }

    /**
     * Check if the requested action is allowed in a get method
     *
     * @param $action
     * @return bool
     */
    public function isAllowedGetAction($action)
    {
        $allowed_get = array(
            FileManager::FILE_INFO
        );
        return in_array($action, $allowed_get);
    }
}
