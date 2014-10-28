<?php

namespace Youwe\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UploadsController
 * @package Youwe\MediaBundle\Controller
 */
class UploadsController extends Controller
{
    /**
     * @author Roelf Otten
     * @param $name
     *
     * @return int|Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function uploadsAction($name)
    {
        $parameters = $this->container->getParameter('youwe_media');
        $root = $parameters['upload_path'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $upath = realpath($root);
        $ufiledir = $upath . '/' . $name;
        $ufilepath = realpath($ufiledir);
        if(substr($ufilepath, 0, strlen($upath)) == $upath)
        {
            $r = new Response(file_get_contents($ufilepath));
            $r->headers->set('Content-Type', finfo_file($finfo, $ufilepath));
            return $r;
        }

        throw $this->createNotFoundException('Error');
        return 0;
    }
}
