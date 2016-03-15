<?php

namespace Kijho\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;

class DefaultController extends Controller {

    public function clientPanelAction($nickname = null, $userId = '', $userType = '') {
        return $this->render('ChatBundle:Default:indexClient.html.twig', array(
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'userType' => $userType,
        ));
    }

    public function exampleClientAction() {
        return $this->render('ChatBundle:Default:exampleClient.html.twig');
    }

    public function adminPanelAction($nickname = null, $userId = '', $userType = '', $local = false) {
        
        $em = $this->getDoctrine()->getManager();
        
        //listado de usuarios que han chateado con el admin, ordenado descendentemente por la fecha del ultimo mensaje
        $lastConversations = $em->getRepository('ChatBundle:Message')->findClientChatNickNames($userId);
        
        
        
        //buscamos las conversaciones completas entre el admin y los clientes
        $allConversations = array();
        $i = 0;
        foreach ($lastConversations as $conversationData) {
            $conversation = $em->getRepository('ChatBundle:Message')->findConversationClientAdmin($conversationData['senderId'], $userId);
            $allConversations[$i]['data'] = $conversationData;
            $allConversations[$i]['messages'] = $conversation;
            $i++;
        }
        
        //\Symfony\Component\VarDumper\VarDumper::dump($allConversations);die();
        
        return $this->render('ChatBundle:Default:indexAdmin.html.twig', array(
                    'local' => $local,
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'userType' => $userType,
                    'lastConversations' => $lastConversations,
                    'allConversations' => $allConversations,
        ));
    }

    public function exampleAdminAction() {
        return $this->render('ChatBundle:Default:exampleAdmin.html.twig');
    }

    /**
     * Esta funcion permite iniciar manualmente el servidor 
     * desde el panel administrador del chat
     * @return JsonResponse
     */
    public function startGosServerAction() {

        $response = array(
            'result' => '__OK__',
            'msg' => 'Server Running...'
        );

        try {
            $process = new Process('./start-gos.sh &');
            $process->start();
            //$this->runProcess($process);
        } catch (\Exception $exc) {
            $response = array(
                'result' => '__KO__',
                'msg' => 'Server error'
            );
        }
        return new JsonResponse($response);
    }

    private function runProcess($process) {
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'OUT > ' . $buffer;
                //return 'OUT > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
                //return 'OUT > ' . $buffer;
            }
        });
    }

}
