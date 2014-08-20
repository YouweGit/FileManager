<?php

namespace Youwe\MediaBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Youwe\MediaBundle\Driver\MediaDriver;
use Youwe\MediaBundle\Form\Type\MediaType;
use Youwe\MediaBundle\Services\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MediaController
 * @package Youwe\MediaBundle\Controller
 */
class MediaController extends Controller {

    /**
     * @param string $dir_path
     * @return Response
     */
    public function listMediaAction($dir_path = null)
    {
        $parameters = $this->container->getParameter('youwe_media');
        $extensions_allowed = $parameters['mime_allowed'];
        $extended_template = $parameters['extended_template'];
        $template = $parameters['template'];
        $root = $parameters['upload_path'];

        if(is_null($dir_path)){
            $dir = $root;
            $dir_path = "";
        } else {
            $dir = $root.DIRECTORY_SEPARATOR.$dir_path;
        }

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $path_valid = $service->checkPath($dir);
        if(!$path_valid){
            $dir = $root;
        }

        $form = $this->createForm(new MediaType);
        try{
            $service->handleFormSubmit($form, $dir);
        } catch(\Exception $e){
            $response = new Response();
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
            return $response;
        }
        $folder_array = explode(DIRECTORY_SEPARATOR,$root);
        $root_folder = array_pop($folder_array);

        $dir_files = scandir($dir);
        $root_dirs = scandir($root);

        /** @var Session $session */
        $session = $this->get('session');
        $display_type = $session->get('display_media_type');

        if($this->getRequest()->get('display_type') != null){
            $display_type = $this->getRequest()->get('display_type');
            $session->set('display_media_type', $display_type);
        } else if (is_null($display_type)){
            $display_type = "file_body_block";
        }

        $file_body_display = $display_type !== null ? $display_type : "file_body_block" ;

        $files = $service->getFileTree($dir_files, $dir, $dir_path);
        $dirs = $service->getDirectoryTree($root_dirs, $root, "");
        $isPopup = $this->getRequest()->get('popup');
        return $this->render($template, array(
            "files" => $files,
            "dirs" => $dirs,
            "current_path" => $dir_path,
            "root_folder" => $root_folder,
            "form" => $form->createView(),
            "upload_allow" => $extensions_allowed,
            "extended_template" => $extended_template,
            "usages" => $parameters['usage_class'],
            "isPopup" => $isPopup,
            "file_body_display" => $file_body_display,
        ));
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function deleteFileAction(){
        $response = new Response();

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $request = $this->getRequest();
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
     * @throws \Exception
     * @return bool
     */
    public function moveFileAction(){
        $response = new Response();

        /** @var MediaDriver $driver */
        $driver = $this->get('youwe.media.driver');

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        $request = $this->getRequest();

        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');
        $target_file = $request->get('target_file');

        try{
            $dir = $service->getFilePath($dir_path, $filename, $target_file);
            $service->checkToken($request->get('token'));
            $driver->moveFile($dir, $filename, $target_file);
        } catch(\Exception $e){
            $response->setContent($e->getMessage());
            $response->setStatusCode($e->getCode() == null ? 500 : $e->getCode());
        }

        return $response;
    }

    /**
     * @throws \Exception
     * @return bool
     */
    public function extractZipAction(){
        $response = new Response();

        $request = $this->getRequest();
        $dir_path = $request->get('dir_path');
        $zip_name = $request->get('zip_name');
        $token = $request->get('token');

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
     * @throws \Exception
     * @return bool
     */
    public function FileInfoAction(){
        $request = $this->getRequest();
        $dir_path = $request->get('dir_path');
        $filename = $request->get('filename');

        $parameters = $this->container->getParameter('youwe_media');
        $root = explode(DIRECTORY_SEPARATOR,$parameters['upload_path']);
        $web_root = end($root);

        /** @var MediaService $service */
        $service = $this->get('youwe.media.service');

        try{
            $response = new JsonResponse();
            $dir = $service->getFilePath($dir_path, $filename);
            $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

            $file_size = $service->humanFilesize($filepath);
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
}