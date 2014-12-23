<?php

namespace Youwe\FileManagerBundle\Driver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Youwe\FileManagerBundle\Model\FileInfo;
use Youwe\FileManagerBundle\Model\FileManager;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerDriver
 * @package Youwe\FileManagerBundle\Driver
 */
class FileManagerDriver
{
    /** Directory Separator */
    const DS = "/";

    /**
     * @var FileManager
     */
    private $file_manager;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param UploadedFile $file
     * @param              $extension
     * @param string       $dir
     * @return bool
     */
    public function handleUploadedFiles(UploadedFile $file, $extension, $dir)
    {
        $original_file = $file->getClientOriginalName();
        $path_parts = pathinfo($original_file);

        $increment = '';
        while (file_exists($dir . FileManager::DS . $path_parts['filename'] . $increment . '.' . $extension)) {
            $increment++;
        }

        $basename = $path_parts['filename'] . $increment . '.' . $extension;
        $file->move($dir, $basename);
    }

    /**
     * @param string $dir_name
     * @throws \Exception - when mimetype is not valid or when something went wrong when creating a dir on filesystem level
     * @return bool
     */
    public function makeDir($dir_name)
    {
        $fm = new Filesystem();
        $dir_path = $this->getFileManager()->getPath($this->getFileManager()->getDirPath(), $dir_name, true);
        if (!file_exists($dir_path)) {
            $fm->mkdir($dir_path, 0755);
        } else {
            $this->throwError("Cannot create directory '" . $dir_name . "': Directory already exists", 500);
        }
    }

    /**
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->file_manager;
    }

    /**
     * @param FileManager $file_manager
     */
    public function setFileManager(FileManager $file_manager)
    {
        $this->file_manager = $file_manager;
    }

    /**
     * @param string          $string - The displayed exception
     * @param int             $code
     * @param null|\Exception $e      - The actual exception
     * @throws \Exception
     */
    public function throwError($string, $code = 500, $e = null)
    {
        if (!$this->getFileManager()->isFullException() || is_null($e)) {
            throw new \Exception($string, $code);
        } else {
            throw new \Exception($string . ": " . $e->getMessage(), $code);
        }
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $new_file_name
     * @throws \Exception - when mimetype is not valid or when something went wrong when renaming on filesystem level
     * @return bool
     */
    public function renameFile(FileInfo $fileInfo, $new_file_name)
    {
        try {
            $this->validateFile($fileInfo, $new_file_name);
            $fm = new Filesystem();
            $old_file = $fileInfo->getFilepath();
            $new_file = $this->getFileManager()->DirTrim($this->getFileManager()->getDir(), $new_file_name, true);
            $fm->rename($old_file, $new_file);
        } catch (\Exception $e) {
            $this->throwError("Cannot rename file or directory", 500, $e);
        }
    }

    /**
     * Validate the files to check if they have a valid file type
     * @param FileInfo $fileInfo
     * @param null     $new_filename
     */
    public function validateFile(FileInfo $fileInfo, $new_filename = null)
    {
        $file_path = $fileInfo->getWebPath(true);
        if (!is_dir($file_path)) {
            $fm = new Filesystem();
            $tmp_dir = $this->createTmpDir($fm);
            $fm->copy($file_path, $tmp_dir . FileManager::DS . $fileInfo->getFilename());

            if (!is_null($new_filename)) {
                $fm->rename($tmp_dir . FileManager::DS . $fileInfo->getFilename(),
                    $tmp_dir . FileManager::DS . $new_filename);
            }
            $this->checkFileType($fm, $tmp_dir);
        }
    }

    /**
     * @param Filesystem $fm
     * @return string
     */
    public function createTmpDir(Filesystem $fm)
    {
        $tmp_dir = $this->getFileManager()->getUploadPath() . FileManager::DS . "." . strtotime("now");
        $fm->mkdir($tmp_dir);

        return $tmp_dir;
    }

    /**
     * Check if the filetype is an valid filetype
     *
     * todo: check if there is a zip in the folder, and check that zip too
     *
     * @param Filesystem $fm
     * @param string     $tmp_dir
     * @throws \Exception - when mimetype is not valid
     */
    public function checkFileType(Filesystem $fm, $tmp_dir)
    {
        $di = new \RecursiveDirectoryIterator($tmp_dir);
        foreach (new \RecursiveIteratorIterator($di) as $filepath => $file) {
            $fileInfo = new FileInfo($filepath, $this->getFileManager());
            $mime_valid = $this->checkMimeType($fileInfo);
            if ($mime_valid !== true) {
                $fm->remove($tmp_dir);
                $this->throwError($mime_valid, 500);
            }
        }
        $fm->remove($tmp_dir);
    }

    /**
     * @param FileInfo $fileInfo
     * @return true
     */
    public function checkMimeType($fileInfo)
    {
        $mime = $fileInfo->getMimetype();
        if ($mime != 'directory') {
            if (!in_array($mime, $this->getFileManager()->getExtensionsAllowed())) {
                return 'Mime type "' . $mime . '" not allowed for file "' . $fileInfo->getFilename() . '"';
            }

            return true;
        } else {
            return true;
        }
    }

    /**
     * @param FileInfo $fileInfo
     * @param string   $new_file_name
     * @throws \Exception - when mimetype is not valid or when something went wrong when moving on filesystem level
     * @return bool
     */
    public function moveFile(FileInfo $fileInfo, $new_file_name)
    {
        try {
            $this->validateFile($fileInfo);
            $file_path = $fileInfo->getFilepath();
            $file = new File($file_path, false);
            $file->move($new_file_name);
        } catch (\Exception $e) {
            $this->throwError("Cannot move file or directory", 500, $e);
        }
    }

    /**
     * @param FileInfo $fileInfo
     * @param          $type
     * @throws \Exception - when mimetype is not valid or when something went wrong when moving on filesystem level
     * @return bool
     */
    public function pasteFile(FileInfo $fileInfo, $type)
    {
        try {
            $target_dir = $this->getFileManager()->getTargetFilepath();
            $target_file = $this->getFileManager()->getTargetFilename();

            $target_file_path = $this->getFileManager()->DirTrim($target_dir, $target_file, true);
            $source_file_path = $fileInfo->getFilepath();
            $this->validateFile($fileInfo);

            $fileSystem = new Filesystem();
            if (!file_exists($target_file_path)) {
                $fileSystem->copy($source_file_path, $target_file_path);

                $cut = $type;
                if ($cut) {
                    $fileSystem->remove($source_file_path);
                }
            } else {
                throw new \Exception(sprintf("File '%s' already exists", $target_file));
            }
        } catch (\Exception $e) {
            $this->throwError("Cannot paste file", 500, $e);
        }
    }

    /**
     * @param FileInfo $fileInfo
     * @throws \Exception - when something went wrong while deleting the file on filesystem level
     * @return bool
     */
    public function deleteFile(FileInfo $fileInfo)
    {
        try {
            $fm = new Filesystem();
            $file = $fileInfo->getFilepath();
            $fm->remove($file);
        } catch (\Exception $e) {
            $this->throwError("Cannot delete file or directory", 500, $e);
        }
    }

    /**
     * @param FileInfo $fileInfo
     * @throws \Exception - when mimetype is not valid or when something went wrong when extracting on filesystem level
     * @return bool
     */
    public function extractZip(FileInfo $fileInfo)
    {
        $chapterZip = new \ZipArchive ();

        $fm = new Filesystem();
        $tmp_dir = $this->createTmpDir($fm);
        var_dump($fileInfo->getFilepath());
        if ($chapterZip->open($fileInfo->getFilepath())) {

            $chapterZip->extractTo($tmp_dir);
            $chapterZip->close();
        }

        $this->checkFileType($fm, $tmp_dir);

        try {
            if ($chapterZip->open($fileInfo->getFilepath())) {

                $chapterZip->extractTo($this->getFileManager()->getDir());
                $chapterZip->close();
            }
        } catch (\Exception $e) {
            $this->throwError("Cannot extract zip", 500, $e);
        }
    }
}
