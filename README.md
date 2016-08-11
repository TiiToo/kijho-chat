kijho-chat
===================
Bundle for chat

Chat plugin for Symfony >= 2.8

Preview
============



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
        host: 127.0.0.1           #The host ip to bind to
        router:
            resources:
                - @ChatBundle/Resources/config/routing/chat.yml
```
Launching the Server

The Server Side WebSocket installation is now complete. You should be able to run this from the root of your symfony installation.

```command
php app/console gos:websocket:server
```

If everything is successful, you will see something similar to the following:

```
Starting Gos WebSocket
Launching Ratchet WS Server on: 127.0.0.1:5555
```

This means the websocket server is now up and running ! 

**From here, only the websocket server is running ! That doesn't mean you can subscribe, publish, call. Follow next step to do it :)**

Ship in production
=======
How run your websocket server in production ?

app/console gos:websocket:server --env=prod
or 
bin/console gos:websocket:server --env=prod

Example with supervisord and other things will come

Fight against memory leak !

So why my memory increase all time ?

In development mode it's normal. (Don't bench memory leaks in this env, never) append your command with --env=prod
Are you using fingers_crossed handler with monolog ? If yes, switch to stream. That's fingers_crossed expected behavior. It stores log entries in memory until event of action_level occurs.
Dependencies of this bundle can have some troubles :( (But I can't do nothing, and if it's the case, downgrade or freeze impacted dependency)
It's your fault :) Dig in you own code.
How bench about memory leaks ?

app/console gos:websocket:server --profile --env=prod
or 
bin/console gos:websocket:server --profile --env=prod

And trigger all the things.

Source
=====
https://github.com/GeniusesOfSymfony/WebSocketBundle/blob/master/README.md
