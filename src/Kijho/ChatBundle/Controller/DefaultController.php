<?php

namespace Kijho\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function chatAction($nickname = null, $authenticated = false)
    {
        return $this->render('ChatBundle:Default:index.html.twig', array(
            'nickname' => $nickname,
            'authenticated' => $authenticated,
        ));
    }
    
    public function exampleAction()
    {
        return $this->render('ChatBundle:Default:example.html.twig');
    }
}
