kijho-chat
===================
Bundle for chat

Chat plugin for Symfony >= 2.8

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require kijho/kijho-chat dev-master
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

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
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Gos\Bundle\WebSocketBundle\GosWebSocketBundle(), //bundle websockets for chat
            new Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle(), //bundle websockets for chat
            new Kijho\ChatBundle\ChatBundle(),
        );

        // ...
    }

    // ...
}
```


In order to see the view, the bundle comes with a implementation.

Import the routing to your `routing.yml`
```yaml
chat:
    resource: "@ChatBundle/Resources/config/routing.yml"
    prefix:   /{_locale}/chat

```
Update the database schema :
```bash
symfony 3.0
bin/console doctrine:schema:update --force

symfony 2.8
app/console doctrine:schema:update --force
```
You must add FrchoCrontaskBundle to the assetic.bundle config
```bash
assetic:
    debug:          "%kernel.debug%"
    use_controller: false
    bundles:        [FrchoCrontaskBundle]
    #java: /usr/bin/java
    filters:
        cssrewrite: ~
        #closure:
     
fkr_css_url_rewrite:
    rewrite_only_if_file_exists: true
    clear_urls: true
```
Enabled locale
=======
```bash
framework:
    translator:      { fallbacks: ["%locale%"] }
```
Web Socket Configuration
=======
```bash
gos_web_socket:
    shared_config: true
    server:
        port: 5555                #The port the socket server will listen on
        host: 0.0.0.0           #The host ip to bind to
        router:
            resources:
                - @ChatBundle/Resources/config/routing/chat.yml
```


