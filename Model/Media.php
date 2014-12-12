<?php

namespace Youwe\MediaBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Youwe\MediaBundle\Driver\MediaDriver;

/**
 * @author Jim Ouwerkerk (j.ouwerkerk@youwe.nl)
 *
 * Class Media
 * @package Youwe\MediaBundle\Model
 */
class Media
{
    //Directory Separator
    const DS = "/";

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

    /** @var  string */
    private $filename = null;

    /** @var  string */
    private $filepath = null;

    /** @var  string */
    private $target_filename = null;

    /** @var  string */
    private $target_filepath = null;

    /** @var  MediaDriver */
    private $driver;

    /** @var  bool */
    private $full_exception;

    /** @var string */
    private $theme_css;

    /** @var string */
    private $display_type;


    /**
     * @param array $parameters
     * @param       $driver
     */
    public function __construct(array $parameters, $driver)
    {
        $this->setExtensionsAllowed($parameters['mime_allowed']);
        $this->setThemeTemplate($parameters['theme']['template']);
        $this->setUploadPath($parameters['upload_path']);
        $this->setUsagesClass($parameters['usage_class']);
        $this->setFullException($parameters['full_exception']);
        $this->setThemeCss($parameters['theme']['css']);
        $this->setWebPath();

        $this->setDriver($driver);
    }

    /**
     * @return mixed|MediaDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param mixed|MediaDriver $driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        $driver->setMedia($this);
    }

    /**
     * @return array
     */
    public function getExtensionsAllowed()
    {
        return $this->extensions_allowed;
    }

    /**
     * @param array $extensions_allowed
     */
    public function setExtensionsAllowed($extensions_allowed)
    {
        $this->extensions_allowed = $extensions_allowed;
    }

    /**
     * @return string
     */
    public function getUploadPath()
    {
        return $this->upload_path;
    }

    /**
     * @param string $root
     */
    public function setUploadPath($root)
    {
        $this->upload_path = $root;
    }

    /**
     * @return string
     */
    public function getThemeTemplate()
    {
        return $this->theme_template;
    }

    /**
     * @param string $theme_template
     */
    public function setThemeTemplate($theme_template)
    {
        $this->theme_template = $theme_template;
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * @return string
     */
    public function getDirPath()
    {
        return $this->dir_path;
    }

    /**
     * @param string $dir_path
     */
    public function setDirPath($dir_path)
    {
        $this->dir_path = $dir_path;
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
     * @return mixed
     */
    public function getUsagesClass()
    {
        return $this->usages_class;
    }

    /**
     * @param mixed $usages_class
     */
    public function setUsagesClass($usages_class)
    {
        $this->usages_class = $usages_class;
    }

    /**
     * Set the correct web path
     */
    private function setWebPath()
    {
        $folder_array = explode(self::DS, $this->getUploadPath());
        $this->web_path = array_pop($folder_array);
    }

    /**
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
     * @return string
     */
    public function getTargetFilepath()
    {
        return $this->target_filepath;
    }

    /**
     * @param string $target_filepath
     */
    public function setTargetFilepath($target_filepath)
    {
        $this->target_filepath = $target_filepath;
    }

    /**
     * @return string
     */
    public function getTargetFilename()
    {
        return $this->target_filename;
    }

    /**
     * @param string $target_filename
     */
    public function setTargetFilename($target_filename)
    {
        $this->target_filename = $target_filename;
    }

    /**
     * @return FileInfo
     */
    public function getFileInfo()
    {
        $fileInfo = new FileInfo($this->getDir() . self::DS . $this->getFilename(), $this);
        return $fileInfo;
    }

    /**
     * @return boolean
     */
    public function isFullException()
    {
        return $this->full_exception;
    }

    /**
     * @param boolean $full_exception
     */
    public function setFullException($full_exception)
    {
        $this->full_exception = $full_exception;
    }

    /**
     * @param Request $request
     */
    public function resolveRequest(Request $request)
    {
        $this->setDirPath($request->get('dir_path'));
        $this->setFilename($request->get('filename'));
        $this->setTargetFilepath($request->get('target_file'));
    }

    /**
     * @param $path - Default is the media dir
     * @throws \Exception - when directory is not in the upload path
     * @return bool
     */
    public function checkPath($path = null)
    {
        if (is_null($path)) {
            $path = $this->getDir();
        }

        $real_path = realpath($path);
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
        $this->getDriver()->extractZip($this->getFileInfo());
    }

    /**
     * @param $type
     */
    public function pasteFile($type)
    {
        $this->getDriver()->pasteFile($this->getFileInfo(), $type);
    }

    /**
     * Move the file
     */
    public function moveFile()
    {
        $target_full_path = $this->getTargetFilepath();
        $this->getDriver()->moveFile($this->getFileInfo(), $target_full_path);
    }

    /**
     * Delete the file
     */
    public function deleteFile()
    {
        $this->getDriver()->deleteFile($this->getFileInfo());
    }

    /**
     * @author Jim Ouwerkerk
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
     * @return string
     */
    public function getThemeCss()
    {
        return $this->theme_css;
    }

    /**
     * @param string $theme_css
     */
    public function setThemeCss($theme_css)
    {
        $this->theme_css = $theme_css;
    }

    /**
     * @return string
     */
    public function getDisplayType()
    {
        return $this->display_type;
    }

    /**
     * @param string $display_type
     */
    public function setDisplayType($display_type)
    {
        $this->display_type = $display_type;
    }
}