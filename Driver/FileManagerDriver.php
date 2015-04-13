<?php

namespace Youwe\FileManagerBundle\Driver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Youwe\FileManagerBundle\Model\FileInfo;
use Youwe\FileManagerBundle\Model\FileManager;

/**
 * @author  Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerDriver
 * @package Youwe\FileManagerBundle\Driver
 */
class FileManagerDriver
{
    /** @var FileManager */
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
     * Upload the files
     *
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
     * Create new directory
     *
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
            $this->getFileManager()
                ->throwError("Cannot create directory '" . $dir_name . "': Directory already exists", 500);
        }
    }

    /**
     * Returns the file manager
     *
     * @return FileManager
     */
    public function getFileManager()
    {
        return $this->file_manager;
    }

    /**
     * Set the file manager
     *
     * @param FileManager $file_manager
     */
    public function setFileManager(FileManager $file_manager)
    {
        $this->file_manager = $file_manager;
    }

    /**
     * Renames the file
     *
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
            $old_file = $fileInfo->getFilepath(true);
            $new_file = $new_file_name;
            $fm->rename($old_file, $new_file);
        } catch (\Exception $e) {
            $this->getFileManager()->throwError("Cannot rename file or directory", 500, $e);
        }
    }

    /**
     * Validate the files to check if they have a valid file type
     *
     * @param FileInfo    $fileInfo
     * @param null|string $new_filename
     */
    public function validateFile(FileInfo $fileInfo, $new_filename = null)
    {
        $file_path = $fileInfo->getWebPath(true);
        if (!is_dir($file_path)) {
            $fm = new Filesystem();
            $tmp_dir = $this->createTmpDir($fm);
            $fm->copy($file_path, $tmp_dir . FileManager::DS . $fileInfo->getFilename());

            if (!is_null($new_filename)) {
                $new_filename = basename($new_filename);
                $fm->rename($tmp_dir . FileManager::DS . $fileInfo->getFilename(),
                    $tmp_dir . FileManager::DS . $new_filename);
            }
            $this->checkFileType($fm, $tmp_dir);
        }
    }

    /**
     * Create a temporary directory
     *
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
     * Check if the file type is an valid file type by extracting the zip inside a temporary directory
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
                $this->getFileManager()->throwError($mime_valid, 500);
            }
        }
        $fm->remove($tmp_dir);
    }

    /**
     * Check the mimetype
     *
     * @param FileInfo $fileInfo
     * @return true
     */
    public function checkMimeType($fileInfo)
    {
        $mime = $fileInfo->getMimetype();
        if ($mime != 'directory' && !in_array($mime, $this->getFileManager()->getExtensionsAllowed())) {
            return 'Mime type "' . $mime . '" not allowed for file "' . $fileInfo->getFilename() . '"';
        }

        return true;
    }

    /**
     * Move the file
     *
     * @param FileInfo $fileInfo
     * @param string   $new_file_name
     * @throws \Exception - when mimetype is not valid or when something went wrong when moving on filesystem level
     * @return bool
     */
    public function moveFile(FileInfo $fileInfo, $new_file_name)
    {
        try {
            $this->validateFile($fileInfo);
            $file_path = $fileInfo->getFilepath(true);
            $file = new File($file_path, false);
            $file->move($new_file_name);
        } catch (\Exception $e) {
            $this->getFileManager()->throwError("Cannot move file or directory", 500, $e);
        }
    }

    /**
     * Paste the file
     *
     * @param FileInfo $fileInfo
     * @param string   $type
     * @throws \Exception - when mimetype is not valid or when something went wrong when moving on filesystem level
     * @return bool
     */
    public function pasteFile(FileInfo $fileInfo, $type)
    {
        try {
            $target_dir = $this->getFileManager()->getTargetFileName();
            $target_file = $this->getFileManager()->getTargetFilepath();

            $target_file_path = $this->getFileManager()->DirTrim($target_dir, $target_file, true);
            $source_file_path = $fileInfo->getFilepath(true);
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
            $this->getFileManager()->throwError("Cannot paste file", 500, $e);
        }
    }

    /**
     * Delete the file
     *
     * @param FileInfo $fileInfo
     * @throws \Exception - when something went wrong while deleting the file on filesystem level
     * @return bool
     */
    public function deleteFile(FileInfo $fileInfo)
    {
        try {
            $fm = new Filesystem();
            $file = $fileInfo->getFilepath(true);
            $fm->remove($file);
        } catch (\Exception $e) {
            $this->getFileManager()->throwError("Cannot delete file or directory", 500, $e);
        }
    }

    /**
     * Extract the zip
     *
     * @param FileInfo $fileInfo
     * @throws \Exception - when mimetype is not valid or when something went wrong when extracting on filesystem level
     * @return bool
     */
    public function extractZip(FileInfo $fileInfo)
    {

        $fm = new Filesystem();
        $tmp_dir = $this->createTmpDir($fm);
        $this->extractZipTo($fileInfo->getFilepath(true), $tmp_dir);
        $this->checkFileType($fm, $tmp_dir);

        try {
            $this->extractZipTo($fileInfo->getFilepath(true), $this->getFileManager()->getDir());
        } catch (\Exception $e) {
            $this->getFileManager()->throwError("Cannot extract zip", 500, $e);
        }
    }

    /**
     * Extract the zip to the given location
     *
     * @param string $filepath
     * @param string $destination
     */
    public function extractZipTo($filepath, $destination)
    {
        $chapterZip = new \ZipArchive ();

        if ($chapterZip->open($filepath)) {
            $chapterZip->extractTo($destination);
            $chapterZip->close();
        }
    }
}
