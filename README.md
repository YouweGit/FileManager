
YouweMediaBundle
==================

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require youwe/media-bundle
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

            new Youwe\MediaBundle\YouweMediaBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
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
    bundles:        [ YouweMediaBundle, ... ]
    #...

```

Step 3: Set the config
-------------------------

```yml
Default Configuration:
youwe_media:
    upload_path: %kernel.root_dir%/../web/uploads
    usage_class: false
    extended_template: YouweMediaBundle:Media:media_layout.html.twig
    template: YouweMediaBundle:Media:media.html.twig
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
        - 'application/x-zip-compressed'
        - 'application/tar'
        - 'application/x-tar'
        - 'text/plain'
        - 'text/x-asm'
        - 'application/octet-stream'
```
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
  You don't want to give an user all the information, like the full upload path, when something went wrong.

Step 4: Add the route
-------------------------

Add the route to the routing.yml

```yml
youwe_media:
    resource: "@YouweMediaBundle/Resources/config/routing.yml"
    options:
        expose: true
```
