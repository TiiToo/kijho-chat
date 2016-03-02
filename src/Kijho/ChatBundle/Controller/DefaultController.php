<?php

namespace Kijho\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    public function clientPanelAction($nickname = null, $userType = '') {
        return $this->render('ChatBundle:Default:indexClient.html.twig', array(
                    'nickname' => $nickname,
                    'userType' => $userType,
        ));
    }

    public function exampleClientAction() {
        return $this->render('ChatBundle:Default:exampleClient.html.twig');
    }

    public function adminPanelAction($nickname = null, $userType = '') {
        return $this->render('ChatBundle:Default:indexAdmin.html.twig', array(
                    'nickname' => $nickname,
                    'userType' => $userType,
        ));
    }
    
    public function exampleAdminAction() {
        return $this->render('ChatBundle:Default:exampleAdmin.html.twig');
    }

}
