<?php

namespace Youwe\MediaBundle\Services;

/**
 * Class MediaSettings
 * @package Youwe\MediaBundle\Services
 */
class MediaSettings {

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

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters){
        $this->setExtensionsAllowed($parameters['mime_allowed']);
        $this->setExtendedTemplate($parameters['extended_template']);
        $this->setTemplate($parameters['template']);
        $this->setUploadPath($parameters['upload_path']);
        $this->setUsagesClass($parameters['upload_path']);
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
            $this->setDir($this->getUploadPath().DIRECTORY_SEPARATOR.$dir_path);
        }

        $path_valid = $service->checkPath($this->getDir());
        if(!$path_valid){
            $this->setDir($this->getUploadPath());
        }
    }


}