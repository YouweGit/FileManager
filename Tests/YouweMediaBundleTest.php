<?php

namespace Youwe\MediaBundle\Test;

use Youwe\MediaBundle\YouweMediaBundle;

/**
 * Class YouweMediaBundleTest
 * @package Youwe\MediaBundle\Test
 */
class YouweMediaBundleTest extends \PHPUnit_Framework_TestCase
{
    public function YouweMediaBundle()
    {
        $bundle = new YouweMediaBundle();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}
