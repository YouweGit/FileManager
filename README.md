
YouweMediaBundle
==================


Installation
-------------
You will also need the FOSJsRoutingBundle and the BmatznerFontAwesomeBundle

https://github.com/bmatzner/BmatznerFontAwesomeBundle
https://github.com/FriendsOfSymfony/FOSJsRoutingBundle

Configuration:

    youwe_media:
        upload_path:        %kernel.root_dir%/../web/uploads
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
Route:

    youwe_media:
        resource: "@YouweMediaBundle/Resources/config/routing.yml"
        options:
            expose: true
