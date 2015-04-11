<?php

namespace Youwe\FileManagerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Youwe\FileManagerBundle\Driver\FileManagerDriver;
use Youwe\FileManagerBundle\Form\Type\FileManagerType;
use Youwe\FileManagerBundle\Model\FileManager;
use Youwe\FileManagerBundle\Services\FileManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Jim Ouwerkerk <j.ouwerkerk@youwe.nl>
 *
 * Class FileManagerController
 * @package Youwe\FileManager\Controller
 */
class FileManagerController extends Controller {

    /**
     * @Route(
     *      "/list/{dir_path}",
     *      name="youwe_file_manager_list",
     *      defaults={"dir_path":null},
     *      options={"expose":true},
     *      requirements={"dir_path":".+"}
     * )
     * @param string $dir_path
     * @return Response
     */
    public function listFilesAction($dir_path = null)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver, $dir_path);

        $form = $this->createForm(new FileManagerType);
        try{
            $service->handleFormSubmit($form);
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
            return $response;
        }

        $renderParameters = $service->getRenderOptions($form);

        return $this->render($fileManager->getThemeTemplate(), $renderParameters);
    }

    /**
     * @Route("/delete", name="youwe_file_manager_delete", defaults={"action":1}, options={"expose":true})
     * @Route("/move", name="youwe_file_manager_move", defaults={"action":2}, options={"expose":true})
     * @Route("/extract", name="youwe_file_manager_extract", defaults={"action":6}, options={"expose":true})
     *
     * @Method("POST")
     *
     * @param Request $request
     * @param int     $action
     *
     * @return bool
     */
    public function requestsPostsFileActions(Request $request, $action)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $fileManager->resolveRequest($request);
        return $service->handleAction($fileManager, $request, $action, true);
    }


    /**
     * @Route("/fileinfo", name="youwe_file_manager_fileinfo", defaults={"action":7}, options={"expose":true})
     *
     * @param Request $request
     * @param int     $action
     *
     * @throws \Exception
     * @return bool
     */
    public function requestsGetFileActions(Request $request, $action)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $fileManager->resolveRequest($request);
        return $service->handleAction($fileManager, $request, $action, false);
    }

    /**
     * @Route("/copy/{type}", name="youwe_file_manager_copy", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @param type - copy or cut
     * @throws \Exception
     * @return bool
     */
    public function copyFileAction(Request $request, $type)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $response = new Response();

        $fileManager->resolveRequest($request);
        $cut = false;
        if($type === 'cut'){
            $cut = true;
        }

        try{
            $dir = $service->getFilePath($fileManager);
            $service->checkToken($request->get('token'));
            $sources = array(
                'display_dir' => $fileManager->getDirPath(),
                'source_dir' => $dir,
                'source_file' => $fileManager->getCurrentFile()->getFilename(),
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
     * @Route("/paste", name="youwe_file_manager_paste", options={"expose":true})
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

        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $fileManager->resolveRequest($request);
        try{
            $dir = $service->getFilePath($fileManager);
            $service->checkToken($request->get('token'));
            $sources = $this->get('session')->get('copy');
            $fileManager->setDir($sources['source_dir']);
            $fileManager->setDirPath($sources['display_dir']);
            $fileManager->setCurrentFile($sources['source_dir'] . FileManager::DS . $sources['source_file']);
            $fileManager->setTargetFilepath($dir . FileManager::DS . $sources['source_file']);
            $type = $sources['cut'];
            $fileManager->pasteFile($type);
            $this->get('session')->remove('copy');
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }

    /**
     * @Route(
     *      "/download/{token}/{path}",
     *      name="youwe_file_manager_download",
     *      requirements={"path"=".+"},
     *      options={"expose":true}
     * )
     *
     * @param Request $request
     * @param         $path
     * @throws \Exception
     * @return Response
     */
    public function DownloadAction(Request $request, $path)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');
        $service->checkToken($request->get('token'));

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $web_path = $fileManager->getPath($path, null, true);
        $content = file_get_contents($web_path);
        $filename = basename($path);
        $response = new Response();

        $response->headers->set('Content-Type', 'mime/type');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename . '"');
        $response->setContent($content);
        return $response;
    }
}