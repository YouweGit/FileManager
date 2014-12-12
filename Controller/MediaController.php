<?php

namespace Youwe\MediaBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Youwe\MediaBundle\Driver\MediaDriver;
use Youwe\MediaBundle\Form\Type\MediaType;
use Youwe\MediaBundle\Model\FileInfo;
use Youwe\MediaBundle\Services\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Jim Ouwerkerk (j.ouwerkerk@youwe.nl)
 *
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
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver, $dir_path);

        $form = $this->createForm(new MediaType);
        try{
            $service->handleFormSubmit($form);
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
            return $response;
        }

        $renderParameters = $service->getRenderOptions($form);

        return $this->render($media->getThemeTemplate(), $renderParameters);
    }

    /**
     * @Route("/delete", name="youwe_media_delete", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @throws \Exception
     * @return bool
     */
    public function deleteFileAction(Request $request)
    {
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $response = new Response();

        $media->resolveRequest($request);
        try{
            $dir = $service->getFilePath($media);
            $media->setDir($dir);
            $service->checkToken($request->get('token'));
            $media->deleteFile();
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
    public function moveFileAction(Request $request)
    {
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $response = new Response();

        $media->resolveRequest($request);

        try{
            $dir = $service->getFilePath($media);
            $media->setDir($dir);
            $service->checkToken($request->get('token'));
            $media->checkPath();
            $media->moveFile();
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }


    /**
     * @Route("/copy/{type}", name="youwe_media_copy", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @param type - copy or cut
     * @throws \Exception
     * @return bool
     */
    public function copyFileAction(Request $request, $type)
    {
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $response = new Response();

        $media->resolveRequest($request);
        $cut = false;
        if($type === 'cut'){
            $cut = true;
        }

        try{
            $dir = $service->getFilePath($media);
            $service->checkToken($request->get('token'));
            $sources = array(
                'display_dir' => $media->getDirPath(),
                'source_dir' => $dir,
                'source_file' => $media->getFilename(),
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
    public function pasteFileAction(Request $request)
    {
        $response = new Response();
        $copy_session = $this->get('session')->get('copy');
        if(is_null($copy_session)){
            return $response;
        }

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $media->resolveRequest($request);
        try{
            $dir = $service->getFilePath($media);
            $service->checkToken($request->get('token'));
            $sources = $this->get('session')->get('copy');
            $media->setDir($sources['source_dir']);
            $media->setDirPath($sources['display_dir']);
            $media->setFilename($sources['source_file']);
            $media->setFilepath($sources['source_dir']);
            $media->setTargetFilepath($dir);
            $media->setTargetFilename($sources['source_file']);
            $type = $sources['cut'];
            $media->pasteFile($type);
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
    public function extractZipAction(Request $request)
    {
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $response = new Response();

        $media->resolveRequest($request);

        try{
            $dir = $service->getFilePath($media);
            $media->setDir($dir);
            $service->checkToken($request->get('token'));
            $media->extractZip();
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
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $media->resolveRequest($request);

        try{
            $response = new JsonResponse();

            $filepath = $media->getPath($media->getDirPath(), $media->getFilename(), true);
            $fileInfo = new FileInfo($filepath, $media);
            $response->setData(json_encode($fileInfo->toArray()));
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }
        return $response;
    }

    /**
     * @Route("/download/{token}/{path}", name="youwe_media_download", requirements={"path"=".+"}, options={"expose":true})
     *
     * @param Request $request
     * @param         $path
     * @throws \Exception
     * @return Response
     */
    public function DownloadAction(Request $request, $path)
    {
        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');
        $service->checkToken($request->get('token'));

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');
        $parameters = $this->container->getParameter('youwe_media');
        $media = $service->createMedia($parameters, $driver);

        $web_path = $media->getPath($path, null, true);
        $content = file_get_contents($web_path);
        $filename = basename($path);
        $response = new Response();

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename);
        $response->setContent($content);
        return $response;
    }
}