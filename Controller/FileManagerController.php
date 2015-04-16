<?php

namespace Youwe\FileManagerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @param string $dir_path the path to the current directory
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
        $renderParameters = $service->getRenderOptions($form);

        return $this->render($fileManager->getThemeTemplate(), $renderParameters);
    }

    /**
     * @Route(
     *      "/upload/{dir_path}",
     *      name="youwe_file_manager_upload",
     *      options={"expose":true},
     *      requirements={"dir_path":".+"}
     * )
     *
     * @param Request $request
     * @param string  $dir_path
     * @return Response
     * @throws \Exception
     */
    public function uploadFileAction(Request $request, $dir_path)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver, $dir_path);

        $form = $this->createForm(new FileManagerType);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $fileManager->checkPath();

                $files = $form->get("file")->getData();
                if (!is_null($files)) {
                    $service->handleUploadFiles($files);
                }
            } else {
                throw new \Exception("Form is invalid", 500);
            }
        }
        return new Response();
    }

    /**
     * @Route("/delete", name="youwe_file_manager_delete", defaults={"action":FileManager::FILE_DELETE}, options={"expose":true})
     * @Route("/move", name="youwe_file_manager_move", defaults={"action":FileManager::FILE_MOVE}, options={"expose":true})
     * @Route("/extract", name="youwe_file_manager_extract", defaults={"action":FileManager::FILE_EXTRACT}, options={"expose":true})
     * @Route("/rename", name="youwe_file_manager_rename", defaults={"action":FileManager::FILE_RENAME}, options={"expose":true})
     * @Route("/new-dir", name="youwe_file_manager_new_dir", defaults={"action":FileManager::FILE_NEW_DIR}, options={"expose":true})
     * @Route("/copy", name="youwe_file_manager_copy", defaults={"action":FileManager::FILE_COPY}, options={"expose":true})
     * @Route("/cut", name="youwe_file_manager_cut", defaults={"action":FileManager::FILE_CUT}, options={"expose":true})
     *
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $action the action that is requested
     *
     * @return Response
     */
    public function requestsPostsFileActions(Request $request, $action)
    {
        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');

        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $fileManager->resolveRequest($request, $action);
        return $service->handleAction($fileManager, $request, $action, true);
    }


    /**
     * @Route("/fileinfo", name="youwe_file_manager_fileinfo", defaults={"action":FileManager::FILE_INFO}, options={"expose":true})
     *
     * @param Request $request
     * @param string  $action the action that is requested
     *
     * @throws \Exception when method is not allowed
     * @return Response
     */
    public function requestsGetFileActions(Request $request, $action)
    {

        /** @var FileManagerService $service */
        $service = $this->get('youwe.file_manager.service');
        if(!$service->isAllowedGetAction($action)){
            throw new \Exception("Method Not Allowed", 405);
        }
        /** @var FileManagerDriver $driver */
        $driver = $this->get('youwe.file_manager.driver');
        $parameters = $this->container->getParameter('youwe_file_manager');
        $fileManager = $service->createFileManager($parameters, $driver);

        $fileManager->resolveRequest($request);
        return $service->handleAction($fileManager, $request, $action, false);
    }

    /**
     * @Route("/paste", name="youwe_file_manager_paste", options={"expose":true})
     * @Method("POST")
     *
     * @param Request $request
     * @return Response
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
            $type = $sources['type'];
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
     * @param string  $path the path of the file that is downloaded
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