<?php

namespace Youwe\FileManagerBundle\Model;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Youwe\FileManagerBundle\Driver\FileManagerDriver;
use Youwe\FileManagerBundle\Events\FileEvent;
use Youwe\FileManagerBundle\YouweFileManagerEvents;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManager
 * @package Youwe\FileManagerBundle\Model
 */
class FileManager
{
    const DS = "/";
    const FILTER_NAME = 'YouweFileManager';
    const FILE_DELETE = 'action-file-delete';
    const FILE_MOVE = 'action-file-move';
    const FILE_COPY= 'action-file-copy';
    const FILE_CUT = 'action-file-cut';
    const FILE_PASTE = 'action-file-paste';
    const FILE_EXTRACT = 'action-file-extract';
    const FILE_INFO = 'action-file-info';
    const FILE_RENAME = 'action-file-rename';
    const FILE_UPLOAD = 'action-file-upload';
    const FILE_NEW_DIR = 'action-file-new-dir';

    /** @var  array - All allowed extensions */
    private $extensions_allowed;

    /** @var  string - Template */
    private $theme_template;

    /** @var  string - Upload path */
    private $upload_path;

    /** @var  string - Current Directory */
    private $dir;

    /** @var  string - Current Directory Path */
    private $dir_path;

    /** @var  string|null */
    private $usages_class;

    /** @var  string */
    private $web_path;

    /** @var  FileManagerDriver */
    private $driver;

    /** @var  bool */
    private $full_exception;

    /** @var string */
    private $theme_css;

    /** @var string */
    private $display_type;

    /** @var string */
    private $magic_file;

    /** @var bool */
    private $filter_images;

    /** @var  ContainerInterface */
    private $container;

    /** @var  string */
    private $filter;

    /** @var  FileInfo */
    private $current_file = null;

    /** @var  FileInfo */
    private $target_file = null;

    /** @var  string */
    private $target_file_path = null;

    /** @var  string */
    private $target_file_name = null;

    /**
     * Constructor
     *
     * Set all the config parameters
     *
     * @param array              $parameters
     * @param                    $driver
     * @param ContainerInterface $container
     */
    public function __construct(array $parameters, $driver, ContainerInterface $container)
    {
        $this->setExtensionsAllowed($parameters['mime_allowed']);
        $this->setThemeTemplate($parameters['theme']['template']);
        $this->setUploadPath($parameters['upload_path']);
        $this->setUsagesClass($parameters['usage_class']);
        $this->setFullException($parameters['full_exception']);
        $this->setThemeCss($parameters['theme']['css']);
        $this->setMagicFile($parameters['magic_file']);
        $this->setFilterImages($parameters['filter_images']);
        $this->setFilter(self::FILTER_NAME);
        $this->setWebPath();
        $this->container = $container;

        $this->setDriver($driver);
    }

    /**
     * Returns the driver object
     *
     * @return mixed|FileManagerDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Sets the driver object
     *
     * @param mixed|FileManagerDriver $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        $driver->setFileManager($this);
    }

    /**
     * Returns all allowed extensions
     *
     * These extension are configured in the config.yml
     *
     * @return array
     */
    public function getExtensionsAllowed()
    {
        return $this->extensions_allowed;
    }

    /**
     * Set the extensions that are allowed
     *
     * @param array $extensions_allowed
     */
    public function setExtensionsAllowed(array $extensions_allowed)
    {
        $this->extensions_allowed = $extensions_allowed;
    }

    /**
     * Returns the path to the upload directory
     *
     * This is configured in the config.yml
     *
     * @return string
     */
    public function getUploadPath()
    {
        return $this->upload_path;
    }

    /**
     * Set the path to the upload directory
     *
     * @param string $root
     */
    public function setUploadPath($root)
    {
        $this->upload_path = $root;
    }

    /**
     * Returns the theme template
     *
     * This is configured in the config.yml
     *
     * @return string
     */
    public function getThemeTemplate()
    {
        return $this->theme_template;
    }

    /**
     * Sets the theme template
     *
     * This is configured in the config.yml
     *
     * @param string $theme_template
     */
    public function setThemeTemplate($theme_template)
    {
        $this->theme_template = $theme_template;
    }

    /**
     * Returns the current directory
     *
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Set the current directory
     *
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Returns the current directory path
     *
     * @return string
     */
    public function getDirPath()
    {
        return $this->dir_path;
    }

    /**
     * Set the current directory path
     *
     * @param string $dir_path
     */
    public function setDirPath($dir_path)
    {
        $this->dir_path = $dir_path;
    }

    /**
     * Returns the usages class
     *
     * This is configured in the config.yml
     * This class requires the function returnUsages.
     *
     * @return mixed
     */
    public function getUsagesClass()
    {
        return $this->usages_class;
    }

    /**
     * Sets the usages class
     *
     * This is configured in the config.yml
     * This class requires the function returnUsages.
     *
     * @param mixed $usages_class
     */
    public function setUsagesClass($usages_class)
    {
        $this->usages_class = $usages_class;
    }

    /**
     * Set the correct web path
     */
    public function setWebPath()
    {
        $folder_array = explode(self::DS, $this->getUploadPath());
        $this->web_path = array_pop($folder_array);
    }

    /**
     * Get the path of the file
     *
     * @param null|string $dir_path
     * @param null|string $filename
     * @param bool        $full_path
     * @return string
     */
    public function getPath($dir_path = null, $filename = null, $full_path = false)
    {
        if ($full_path) {
            $path = $this->upload_path;
        } else {
            $path = $this->web_path;
        }
        if (!is_null($dir_path)) {
            $path = self::DS . $this->DirTrim($path, $dir_path);
        }
        if (!is_null($filename)) {
            $path = self::DS . $this->DirTrim($path, $filename);
        }
        return $path;
    }

    /**
     * Set the dir paths
     *
     * @param null|string $dir_path
     * @throws \Exception
     */
    public function setDirPaths($dir_path)
    {
        if (is_null($dir_path)) {
            $this->setDir($this->getUploadPath());
            $this->setDirPath("");
        } else {
            $this->setDirPath($this->DirTrim($dir_path));
            $this->setDir($this->getUploadPath() . self::DS . $dir_path);
        }

        $path_valid = $this->checkPath($this->getDir());
        if (!$path_valid) {
            $this->setDir($this->getUploadPath());
        }
    }

    /**
     * Check if the full exception should be displayed
     *
     * This is configured in the config.yml
     *
     * @return boolean
     */
    public function isFullException()
    {
        return $this->full_exception;
    }

    /**
     * Sets the full exception parameter
     *
     * This is configured in the config.yml
     *
     * @param boolean $full_exception
     */
    public function setFullException($full_exception)
    {
        $this->full_exception = $full_exception;
    }

    /**
     * Set the required request parameters to the object
     *
     * @param Request  $request
     * @param int|null $action
     */
    public function resolveRequest(Request $request, $action = null)
    {
        $this->setDirPath($request->get('dir_path'));

        $this->setCurrentFile($this->getPath($this->getDirPath(), $request->get('filename'), true));

        if($action === self::FILE_RENAME && !$this->getCurrentFile()->isDir()){
            $extension = $this->getCurrentFile()->getExtension();
            $target_file = $request->get('target_file') . "." . $extension;
        } elseif($action === self::FILE_PASTE) {

            $sources = $this->container->get('session')->get('copy');
            $this->setCurrentFile($sources['source_dir'] . FileManager::DS . $sources['source_file']);
            $target_file = $request->get('target_file') . FileManager::DS . $sources['source_file'];
        } else {
            $target_file = $request->get('target_file');
        }

        if(isset($target_file)){
            $this->setTargetFile($target_file);
        }
    }

    /**
     * Check if the path is in the upload directory
     *
     * @param $path - Default is the file dir
     * @throws \Exception - when directory is not in the upload path
     * @return bool
     */
    public function checkPath($path = null)
    {
        if (is_null($path)) {
            $path = $this->getDir();
        }

        $real_path = realpath($path);
        if(!$real_path){
            $real_path = realpath(dirname($path));
        }
        $upload_path = realpath($this->getUploadPath());

        if (strcasecmp($real_path, $upload_path > 0)) {
            return true;
        } else {
            throw new \Exception("Directory is not in the upload path", 403);
        }
    }

    /**
     * Extract the zip
     */
    public function extractZip()
    {
        $this->event(YouweFileManagerEvents::BEFORE_FILE_EXTRACTED);
        $this->getDriver()->extractZip($this->getCurrentFile());
        $this->event(YouweFileManagerEvents::AFTER_FILE_EXTRACTED);
    }

    /**
     * Paste the file
     *
     * @param $type
     */
    public function pasteFile($type)
    {
        $this->event(YouweFileManagerEvents::BEFORE_FILE_PASTED);
        $this->resolveImage();
        $this->getDriver()->pasteFile($this->getCurrentFile(), $type);
        $this->event(YouweFileManagerEvents::AFTER_FILE_PASTED);
    }

    /**
     * Move the file
     */
    public function moveFile()
    {
        $this->event(YouweFileManagerEvents::BEFORE_FILE_MOVED);
        $target_full_path = $this->getTargetFile()->getFilepath(true);
        $this->getDriver()->moveFile($this->getCurrentFile(), $target_full_path);
        $this->resolveImage();
        $this->getCurrentFile()->setFilepath($target_full_path);
        $this->event(YouweFileManagerEvents::AFTER_FILE_MOVED);
    }

    /**
     * Delete the file
     */
    public function deleteFile()
    {
        $this->event(YouweFileManagerEvents::BEFORE_FILE_DELETED);
        $this->resolveImage();
        $this->getDriver()->deleteFile($this->getCurrentFile());
        $this->event(YouweFileManagerEvents::AFTER_FILE_DELETED);
    }

    /**
     * Rename the file
     */
    public function renameFile()
    {
        $this->event(YouweFileManagerEvents::BEFORE_FILE_RENAMED);
        $target_full_path = $this->getTargetFile()->getFilepath(true);
        $this->getDriver()->renameFile($this->getCurrentFile(), $target_full_path);
        $this->resolveImage();
        $this->getCurrentFile()->setFilepath($target_full_path);
        $this->event(YouweFileManagerEvents::AFTER_FILE_RENAMED);
    }

    /**
     * Create a new directory
     */
    public function newDirectory()
    {
        $target_full_path = $this->getTargetFile()->getFilepath(true);
        $this->event(YouweFileManagerEvents::BEFORE_FILE_DIR_CREATED);
        $this->getDriver()->makeDir($target_full_path);
        $this->event(YouweFileManagerEvents::AFTER_FILE_DIR_CREATED);
    }

    /**
     * @param string $event_key
     */
    public function event($event_key){
        $dispatcher = $this->container->get('event_dispatcher');
        $event = new FileEvent($this->getCurrentFile());
        $dispatcher->dispatch($event_key, $event);
    }

    /**
     * Trim the directory separators from the file(path)
     *
     * @param string $path
     * @param string $file
     * @param bool   $rTrim
     * @return string
     */
    public function DirTrim($path, $file = null, $rTrim = false)
    {
        if ($rTrim) {
            $result = rtrim($path, self::DS);
        } else {
            $result = trim($path, self::DS);
        }

        if (!is_null($file)) {
            $file_result = trim($file, self::DS);
        } else {
            $file_result = $file;
        }

        if (!is_null($file_result)) {
            $result = $result . self::DS . $file_result;
        }

        return $result;
    }

    /**
     * Returns the CSS file for the theme
     *
     * @return string
     */
    public function getThemeCss()
    {
        return $this->theme_css;
    }

    /**
     * Set the CSS file for the theme
     *
     * @param string $theme_css
     */
    public function setThemeCss($theme_css)
    {
        $this->theme_css = $theme_css;
    }

    /**
     * Returns the display type (block or list)
     *
     * @return string
     */
    public function getDisplayType()
    {
        return $this->display_type;
    }

    /**
     * Set the display type (block or list)
     *
     * @param string $display_type
     */
    public function setDisplayType($display_type)
    {
        $this->display_type = $display_type;
    }

    /**
     * Returns the location of the Mimetype Magic file
     *
     * @return string
     */
    public function getMagicFile()
    {
        return $this->magic_file;
    }

    /**
     * Set the location of the Mimetype Magic file
     *
     * @param string $magic_file
     */
    public function setMagicFile($magic_file)
    {
        $this->magic_file = $magic_file;
    }

    /**
     * Remove the image of the current file
     *
     * @throws \Exception - If Liip Imagine Bundle is not installed
     */
    public function resolveImage()
    {
        if($this->FilterImages() && $this->getCurrentFile()->isImage()){
            try{
                $imageCacheManager = $this->getCacheManager();
            } catch(\Exception $e){
                $exception = 'Cannot resolve the image. Please make sure that LiipImagineBundle is installed';
                if (!$this->isFullException() || is_null($e)) {
                    Throw new \Exception($exception);
                } else {
                    throw new \Exception($exception . ": " . $e->getMessage());
                }

            }
            $imageCacheManager->remove($this->getCurrentFile()->getWebPath(), $this->getFilter());
        }
    }

    /**
     * Return the liip cache manager
     *
     * @return CacheManager
     */
    public function getCacheManager()
    {
        return $this->container->get('liip_imagine.cache.manager');
    }

    /**
     * Return the liip data manager
     *
     * @return DataManager
     */
    public function getDataManager()
    {
        return $this->container->get('liip_imagine.data.manager');
    }

    /**
     * Return the liip filter manager
     *
     * @return FilterManager
     */
    public function getFilterManager()
    {
        return $this->container->get('liip_imagine.filter.manager');
    }

    /**
     * Check if the images should be filtered
     *
     * @return bool
     */
    public function FilterImages()
    {
        return $this->filter_images;
    }

    /**
     * Set the filter_images parameter
     *
     * @param bool $filter_images
     */
    public function setFilterImages($filter_images)
    {
        $this->filter_images = $filter_images;
    }

    /**
     * Returns the filter name
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the filter name
     *
     * @param string $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Returns the FileInfo object of the current file
     *
     * @return FileInfo|null
     */
    public function getCurrentFile()
    {
        return $this->current_file;
    }

    /**
     * Set the FileInfo object of the current file
     *
     * @param string $current_filepath
     */
    public function setCurrentFile($current_filepath)
    {
        $this->current_file = new FileInfo($current_filepath, $this);
    }

    /**
     * Returns the FileInfo object of the target file
     *
     * @return FileInfo|null
     */
    public function getTargetFile()
    {
        return $this->target_file;
    }

    /**
     * Set the FileInfo object of the target file
     *
     * @param string $target_filepath
     */
    public function setTargetFile($target_filepath)
    {
        $this->target_file = new FileInfo($target_filepath, $this);
    }


    /**
     * Throw the error based on the full exception config
     *
     * @param string          $string - The displayed exception
     * @param int             $code
     * @param null|\Exception $e      - The actual exception
     * @throws \Exception
     */
    public function throwError($string, $code = 500, $e = null)
    {
        if (!$this->isFullException() || is_null($e)) {
            throw new \Exception($string, $code);
        } else {
            throw new \Exception($string . ": " . $e->getMessage(), $code);
        }
    }

    /**
     * Set the target file path
     *
     * @param $string
     */
    public function setTargetFilepath($string)
    {
        $this->target_file_path = $string;
    }

    /**
     * Returns the target file path
     *
     * @return string
     */
    public function getTargetFilepath()
    {
        return $this->target_file_path;
    }

    /**
     * Returns the target file name
     *
     * @return string
     */
    public function getTargetFileName()
    {
        return $this->target_file_name;
    }

    /**
     * Set the target file name
     *
     * @param string $target_file_name
     */
    public function setTargetFileName($target_file_name)
    {
        $this->target_file_name = $target_file_name;
    }
}