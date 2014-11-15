<?php

namespace Youwe\MediaBundle\Tests\Services;

use Youwe\MediaBundle\Services\Utils;

/**
 * Class UtilsTest
 * @package Youwe\MediaBundle\Tests\Services
 */
class UtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @author Jim Ouwerkerk
     * Test if all the trim variations are working
     */
    public function testDirTrim()
    {
        $path = "/upload/path/";
        $trimmed = Utils::DirTrim($path);
        $this->assertEquals("upload/path", $trimmed);

        $path = "/upload/path/";
        $trimmed = Utils::DirTrim($path, null, true);
        $this->assertEquals("/upload/path", $trimmed);

        $path = "/upload/path/";
        $file = "/filename.txt";
        $trimmed = Utils::DirTrim($path, $file);
        $this->assertEquals("upload/path/filename.txt", $trimmed);

        $path = "/upload/path";
        $file = "/filename.txt";
        $trimmed = Utils::DirTrim($path, $file);
        $this->assertEquals("upload/path/filename.txt", $trimmed);


        $path = "/upload/path/";
        $file = "/filename.txt";
        $trimmed = Utils::DirTrim($path, $file, true);
        $this->assertEquals("/upload/path/filename.txt", $trimmed);
    }

    /**
     * @author Jim Ouwerkerk
     * Test if the readable size is correct
     */
    public function testReadableSize()
    {
        $size1 = Utils::readableSize(1024);
        $size2 = Utils::readableSize(903424);
        $size3 = Utils::readableSize(51380224);
        $size4 = Utils::readableSize(4294967296);
        $size5 = Utils::readableSize(4831838208);
        $this->assertEquals("1.00 KB", $size1);
        $this->assertEquals("882.25 KB", $size2);
        $this->assertEquals("49.00 MB", $size3);
        $this->assertEquals("4.00 GB", $size4);
        $this->assertEquals("4.50 GB", $size5);
    }
}
