
YouweMediaBundle
==================

Dev Docs: https://confluence.youwe.nl/display/ooipdev/Youwe+Media+Bundle

Installation
-------------
Add the bundle to composer.json

    composer require youwe/media-bundle:dev-master

Add the bundle to the AppKernel

    public function registerBundles()
        {
            $bundles = array(
                //...
                new Youwe\MediaBundle\YouweMediaBundle(),
                new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
                //...
            );
    
            return $bundles;
        }

You will also need the FOSJsRoutingBundle

https://github.com/FriendsOfSymfony/FOSJsRoutingBundle

Default Configuration:

    youwe_media:
        upload_path:        %kernel.root_dir%/../web/uploads
        usage_class: ~
        template: ~
        extended_template: ~
        full_exceptions: false
        mime_allowed:
            - 'image/png'
            - 'image/jpg'
            - 'image/jpeg'
            - 'image/gif'
            - 'application/pdf'
            - 'application/ogg'
            - 'video/mp4'
            - 'application/zip'
            - 'multipart/x-zip'
            - 'application/rar'
            - 'application/x-rar-compressed'
            - 'application/tar'
            - 'application/x-tar'
            - 'text/html'
            - 'text/javascript'
            - 'text/css'
            - 'text/xml'
            - 'text/plain'
            - 'text/x-asm'
            - 'application/xml'
            - 'application/octet-stream'
            - 'application/x-shockwave-flash'

Optional config:

* <b>usage_class</b> <br>
  This is where the usage class is defined. <br>
  It requires the function 'returnUsages' that returns a array with strings of the usage locations.
* <b>template</b><br>
  The template of the media manager. <br>
  This template should extend the media template and you have to include the media block: {{ block('media_block') }}
* <b>extended_template</b><br>
  The media template will extend with the given template.<br>
  For example: you can define your layout template in here.
* <b>full_exceptions</b><br>
  If true, display the exception in the error modal.<br>
  When you leave it false, it will not show the full error for security reasons.<br>
  You don't want to give an user all the information, like the full upload path, when something went wrong.<br>

Route:

    youwe_media:
        resource: "@YouweMediaBundle/Resources/config/routing.yml"
        options:
            expose: true
