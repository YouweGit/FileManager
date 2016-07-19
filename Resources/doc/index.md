
YouweFileManagerBundle
==================

Requirements
============
This bundle requires the [FOS Routing Bundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle)

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require youwe/file-manager-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding the following line in the `app/AppKernel.php`
file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Youwe\FileManagerBundle\YouweFileManagerBundle(),
        );

        // ...
    }

    // ...
}
```

Add the bundle in the the assetic config:

```yml
# Assetic Configuration
assetic:
    #...
    bundles:        [ YouweFileManagerBundle, ... ]
    #...

```

Step 3: Set the config
-------------------------

Default Configuration:

```yml
youwe_file_manager:
    upload_path: %kernel.root_dir%/../web/uploads
    full_exception: false
    theme:
        css: "/bundles/youwefilemanager/css/simple/default.css"
        template: "YouweFileManagerBundle:FileManager:file_manager.html.twig"
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
        - 'application/x-zip-compressed'
        - 'application/tar'
        - 'application/x-tar'
        - 'text/plain'
        - 'text/x-asm'
        - 'application/octet-stream'
```

Optional config:

* <b>theme</b><br>
  You can define your own css and template here.
* <b>full_exceptions</b><br>
  If true, display the exception in the error modal.<br>
  When you leave it false, it will not show the full error for security reasons.<br>
  You don't want to give an user all the information, like the full upload path, when something went wrong.

Step 4: Add the route
-------------------------

Add the route to the routing.yml

```yml
youwe_file_manager:
    resource: "@YouweFileManagerBundle/Resources/config/routing.yml"
    options:
        expose: true
```

CKEditor
-------------------------

Set the route and the route parameters in the ckeditor config

```php
// Example for Ivory CKEditor Bundle
$form = $this->createFormBuilder()
            ->add('content', 'ckeditor', array(
                'config' => array(
                    'filebrowserImageBrowseUrl' => array(
                        'route'            => 'youwe_file_manager_list',
                        'route_parameters' => array('popup'=>'1'),
                    ),
                ),
            ))->getForm();
```