<?php

namespace Youwe\FileManager\Test;

use Youwe\FileManagerBundle\YouweFileManagerBundle;

/**
 * Class YouweFileManagerBundleTest
 * @package Youwe\FileManager\Test
 */
class YouweFileManagerBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testYouweFileManagerBundle()
    {
        $bundle = new YouweFileManagerBundle();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
