<?php

namespace Youwe\MediaBundle\Model;

use Youwe\MediaBundle\Services\MediaService;
use Youwe\MediaBundle\Services\Utils;

/**
 * Class Media
 * @package Youwe\MediaBundle\Model
 */
class Media {

    /** @var  array - All allowed extensions*/
    private $extensions_allowed;

    /** @var  string - Extended Template*/
    private $extended_template;

    /** @var  string - Template*/
    private $template;

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

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters){
        $this->setExtensionsAllowed($parameters['mime_allowed']);
        $this->setExtendedTemplate($parameters['extended_template']);
        $this->setTemplate($parameters['template']);
        $this->setUploadPath($parameters['upload_path']);
        $this->setUsagesClass($parameters['usage_class']);
        $this->setWebPath();
    }

    /**
     * @return string
     */
    public function getExtendedTemplate() {
        return $this->extended_template;
    }

    /**
     * @param string $extended_template
     */
    public function setExtendedTemplate($extended_template) {
        $this->extended_template = $extended_template;
    }

    /**
     * @return array
     */
    public function getExtensionsAllowed() {
        return $this->extensions_allowed;
    }

    /**
     * @param array $extensions_allowed
     */
    public function setExtensionsAllowed($extensions_allowed) {
        $this->extensions_allowed = $extensions_allowed;
    }

    /**
     * @return string
     */
    public function getUploadPath() {
        return $this->upload_path;
    }

    /**
     * @param string $root
     */
    public function setUploadPath($root) {
        $this->upload_path = $root;
    }

    /**
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getDir() {
        return $this->dir;
    }

    /**
     * @param string $dir
     */
    public function setDir($dir) {
        $this->dir = $dir;
    }

    /**
     * @return string
     */
    public function getDirPath() {
        return $this->dir_path;
    }

    /**
     * @param string $dir_path
     */
    public function setDirPath($dir_path) {
        $this->dir_path = $dir_path;
    }

    /**
     * @return mixed
     */
    public function getUsagesClass() {
        return $this->usages_class;
    }

    /**
     * @param mixed $usages_class
     */
    public function setUsagesClass($usages_class) {
        $this->usages_class = $usages_class;
    }

    private function setWebPath()
    {
        $folder_array = explode(DIRECTORY_SEPARATOR, $this->getUploadPath());
        $this->web_path = array_pop($folder_array);
    }

    /**
     * @author Jim Ouwerkerk
     * @param null|string $dir_path
     * @param null|string $filename
     * @param bool        $full_path
     * @return string
     */
    public function getPath($dir_path = null, $filename = null, $full_path = false)
    {
        if($full_path){
            $path = $this->upload_path;
        } else {
            $path = $this->web_path;
        }
        if (!is_null($dir_path)) {
            $path = DIRECTORY_SEPARATOR . Utils::DirTrim($path, $dir_path);
        }
        if (!is_null($filename)) {
            $path = DIRECTORY_SEPARATOR . Utils::DirTrim($path, $filename);
        }
        return $path;
    }

    public function checkPath(){
        if (($filename == "" && !is_null($target_file))) {
            throw new \Exception("Filename cannot be empty when there is a target path");
        }

        $root = $this->settings->getUploadPath();

        if (empty($dir_path)) {
            $dir = $root;
        } else {
            $dir = str_replace("../", "", $dir_path);
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
     * @author Jim Ouwerkerk
     * @param MediaService $service
     * @param              $dir_path
     * @throws \Exception
     */
    public function setDirPaths(MediaService $service, $dir_path) {
        if(is_null($dir_path)){
            $this->setDir($this->getUploadPath());
            $this->setDirPath("");
        } else {
            $this->setDirPath(Utils::DirTrim($dir_path));
            $this->setDir($this->getUploadPath().DIRECTORY_SEPARATOR.$dir_path);
        }

        $path_valid = $service->checkPath($this->getDir());
        if(!$path_valid){
            $this->setDir($this->getUploadPath());
        }
    }
}