<?php

namespace Youwe\MediaBundle\Driver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class MediaDriver
 * @package Youwe\MediaBundle\Driver
 */
class MediaDriver
{
    /**
     * Contains all mime types that are allowed to upload.
     * @var array
     */
    public $mime_allowed;

    /**
     * The path to the upload directory
     * @var string
     */
    public $upload_path;

    /**
     * The class where the function is defined for getting
     * the usages amount of the media item
     *
     * This class should have the function returnUsage();
     * @var
     */
    public $usage_class;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $parameters = $this->container->getParameter('youwe_media');
        $this->upload_path = $parameters['upload_path'];
        $this->mime_allowed = $parameters['mime_allowed'];
    }


    /**
     * @param $files
     * @param $dir
     * @return string
     * @throws \Exception
     */
    public function handleFiles($files, $dir){
        /** @var UploadedFile $file */
        foreach($files as $file){

            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            }

            if(in_array($file->getClientMimeType(), $this->mime_allowed)){
                $original_file = $file->getClientOriginalName();
                $path_parts = pathinfo($original_file);

                $increment = '';
                while(file_exists($dir . "/" . $path_parts['filename'] . $increment . '.' . $extension)) {
                    $increment++;
                }

                $basename = $path_parts['filename'] . $increment . '.' . $extension;
                $file->move($dir,$basename);
            } else {
                throw new \Exception("Mimetype is not allowed", 500);
            }
        }
        return true;
    }

    /**
     * @param $dir
     * @param $dir_name
     * @throws \Exception
     * @return bool
     */
    public function makeDir($dir, $dir_name){
        $fm = new Filesystem();
        $dir_path = rtrim($dir,"/") . "/" . $dir_name;
        if(!file_exists($dir_path)){
            $fm->mkdir($dir_path, 0700);
        } else {
            throw new \Exception("Cannot create directory '" . $dir_name ."': Directory already exists", 500);
        }
    }

    /**
     * @param $dir
     * @param $file_name
     * @param $new_file_name
     * @return bool
     */
    public function renameFile($dir, $file_name, $new_file_name){
        $fm = new Filesystem();
        $old_file = rtrim($dir,"/") . "/" . $file_name;
        $new_file = rtrim($dir,"/") . "/" . $new_file_name;
        $fm->rename($old_file, $new_file);
    }

    /**
     * @param $dir
     * @param $file_name
     * @param $new_file_name
     * @return bool
     */
    public function moveFile($dir, $file_name, $new_file_name){
        $file = rtrim($dir,"/") . "/" . $file_name;
        $fm = new File($file, false);
        $fm->move($new_file_name);
    }

    /**
     * @param $dir
     * @param $file_name
     * @return bool
     */
    public function deleteFile($dir, $file_name){
        $fm = new Filesystem();
        $file = rtrim($dir,"/") . "/" . $file_name;
        $fm->remove($file);
    }

    /**
     * @param $dir
     * @param $zip_file
     * @throws \Exception
     * @return bool
     */
    public function extractZip($dir, $zip_file){
        $chapterZip = new \ZipArchive ();
        $tmp_dir = $this->upload_path . "/" . "." . strtotime("now");
        $fm = new Filesystem();
        $fm->mkdir($tmp_dir);

        if ($chapterZip->open ( $dir . "/" . $zip_file )) {
            $chapterZip->extractTo($tmp_dir);
            $chapterZip->close();
        }
        $di = new \RecursiveDirectoryIterator($tmp_dir);
        foreach (new \RecursiveIteratorIterator($di) as $filename => $file) {
            $mime_valid = $this->checkMimeType($filename);
            if($mime_valid !== true){
                $fm->remove($tmp_dir);
                throw new \Exception($mime_valid, 500);
            }
        }
        $fm->remove($tmp_dir);
        if ($chapterZip->open ( $dir . "/" . $zip_file )) {

            $chapterZip->extractTo($dir);
            $chapterZip->close();
        }
    }

    /**
     * @param $name
     * @return false
     */
    public function checkMimeType($name){
        $mime = mime_content_type($name);

        if($mime !=  'directory'){
            if (!in_array($mime, $this->mime_allowed)) {
                return 'Mime type "'.mime_content_type($name).'" not allowed for file "'. basename($name) .'"';
            }
            return true;
        }
        else {
            return true;
        }
    }
}
