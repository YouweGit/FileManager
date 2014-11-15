<?php

namespace Youwe\MediaBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Youwe\MediaBundle\Driver\MediaDriver;
use Youwe\MediaBundle\Form\Type\MediaType;
use Youwe\MediaBundle\Services\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Youwe\MediaBundle\Model\Media;
use Youwe\MediaBundle\Services\Utils;

/**
 * Class MediaController
 * @package Youwe\MediaBundle\Controller
 */
class MediaController extends Controller {

    /**
     * @Route(
     *      "/list/{dir_path}",
     *      name="youwe_media_list",
     *      defaults={"dir_path":null},
     *      options={"expose":true},
     *      requirements={"dir_path":".+"}
     * )
     * @param string $dir_path
     * @return Response
     */
    public function listMediaAction($dir_path = null)
    {
        $parameters = $this->container->getParameter('youwe_media');
        $settings = new Media($parameters);

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $settings->setDirPaths($service, $dir_path);
        $form = $this->createForm(new MediaType);
        try{
            $service->handleFormSubmit($form, $settings->getDir());
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
            return $response;
        }

        $renderParameters = $service->getRenderOptions($settings, $form);

        return $this->render($settings->getTemplate(), $renderParameters);
    }

    /**
     * @Route("/delete", name="youwe_media_delete", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function deleteFileAction(Request $request){
        $response = new Response();

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');

        try{
            $dir = $service->getFilePath($dir_path, $filename);
            $service->checkToken($request->get('token'));
            $driver->deleteFile($dir, $filename);
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }
        return $response;
    }

    /**
     * @Route("/move", name="youwe_media_move", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function moveFileAction(Request $request){
        $response = new Response();

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');
        $target_file = $request->get('target_file');

        try{
            $dir = $service->getFilePath($dir_path, $filename, $target_file);
            $service->checkToken($request->get('token'));
            $service->checkPath($dir);
            $driver->moveFile($dir, $filename, $target_file);
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }


    /**
     * @Route("/copy", name="youwe_media_copy", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @param type - copy or cut
     * @throws \Exception
     * @return bool
     */
    public function copyFileAction(Request $request, $type){
        $response = new Response();

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');

        $cut = false;
        if($type === 'cut'){
            $cut = true;
        }

        try{
            $dir = $service->getFilePath($dir_path, $filename);
            $service->checkToken($request->get('token'));
            $sources = array(
                'display_dir' => $dir_path,
                'source_dir' => $dir,
                'source_file' => $filename,
                'cut' => $cut
            );
            $this->get('session')->set('copy', $sources);
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }

    /**
     * @Route("/paste", name="youwe_media_paste", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function pasteFileAction(Request $request){
        $response = new Response();

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');

        try{
            $dir = $service->getFilePath($dir_path, $filename);
            $service->checkToken($request->get('token'));
            $sources = $this->get('session')->get('copy');
            $filename = $sources['source_file'];
            $targets = array('target_dir' => $dir, 'target_file' => $filename);
            $driver->pasteFile($sources, $targets);
            $this->get('session')->remove('copy');
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }

    /**
     * @Route("/extract", name="youwe_media_extract", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function extractZipAction(Request $request){
        $response = new Response();

        $dir_path = $request->get('dir_path');
        $zip_name = $request->get('zip_name');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        try{
            $dir = $service->getFilePath($dir_path, $zip_name);
            $service->checkToken($request->get('token'));
            $driver->extractZip($dir, $zip_name);
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }
        return $response;
    }

    /**
     * @Route("/fileinfo", name="youwe_media_fileinfo", options={"expose":true})
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function FileInfoAction(Request $request)
    {
        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');

        $parameters = $this->container->getParameter('youwe_media');
        $settings = new Media($parameters);
        $root = explode(DIRECTORY_SEPARATOR, $settings->getUploadPath());
        $web_root = end($root);

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        try{
            $response = new JsonResponse();
            $dir = $service->getFilePath($dir_path, $filename);
            $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

            $file_size = Utils::readableSize(filesize($filepath));
            $file_modification = date("Y-m-d H:i:s", filemtime($filepath));
            $mimetype = mime_content_type($filepath);

            $file_info = array();
            $file_info['filename'] = $dir_path . DIRECTORY_SEPARATOR . $filename;
            $file_info['mimetype'] = $mimetype;
            $file_info['modified'] = $file_modification;
            $file_info['filesize'] = $file_size;
            $file_info['usages'] = $service->getUsages($filename, $dir_path);
            $file_info['filepath'] = $web_root . DIRECTORY_SEPARATOR . $dir_path;

            $response->setData(json_encode($file_info));
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }
        return $response;
    }

    /**
     * @author Jim Ouwerkerk
     * @Route("/download/{path}", name="youwe_media_download", requirements={"path"=".+"}, options={"expose":true})
     *
     * @return Response
     * @throws \Exception
     */
    public function DownloadAction($path)
    {
        $parameters = $this->container->getParameter('youwe_media');
        $media = new Media($parameters);
        $web_path = Utils::DirTrim($media->getWebPath($path));
        $content = file_get_contents($web_path);
        $filename = basename($path);
        $response = new Response();

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);
        $response->setContent($content);
        return $response;
    }
}