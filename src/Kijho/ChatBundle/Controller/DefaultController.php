<?php

namespace Kijho\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Kijho\ChatBundle\Entity\Message;
use Kijho\ChatBundle\Entity\UserChatSettings;
use Kijho\ChatBundle\Entity\ChatSettings;
use Kijho\ChatBundle\Form\UserChatSettingsType;
use Kijho\ChatBundle\Form\ChatSettingsType;
use Kijho\ChatBundle\Form\ContactFormType;

class DefaultController extends Controller {

    public function clientPanelAction($nickname = null, $userId = '', $userType = '', $local = false) {
        $em = $this->getDoctrine()->getManager();

        //buscamos las configuraciones del usuario, sino tiene se las creamos
        $searchUserSettings = array('userId'=>$userId, 'userType' => $userType);
        $userSettings = $em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
        if (!$userSettings) {
            $userSettings = new UserChatSettings();
            $userSettings->setUserId($userId);
            $userSettings->setUserType($userType);
            $em->persist($userSettings);
            $em->flush();
        }
        
        $userSettingsForm = $this->createForm(UserChatSettingsType::class, $userSettings);
        $contactForm = $this->createForm(ContactFormType::class);
        
        
        return $this->render('ChatBundle:Default:indexClient.html.twig', array(
                    'local' => $local,
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'userType' => $userType,
                    'userSettings' => $userSettings,
                    'userSettingsForm' => $userSettingsForm->createView(),
                    'contactForm' => $contactForm->createView(),
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
        
        //buscamos las configuraciones del usuario, sino tiene se las creamos
        $searchUserSettings = array('userId'=>$userId, 'userType' => $userType);
        $userSettings = $em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
        if (!$userSettings) {
            $userSettings = new UserChatSettings();
            $userSettings->setUserId($userId);
            $userSettings->setUserType($userType);
            $em->persist($userSettings);
            $em->flush();
        }
        
        $userSettingsForm = $this->createForm(UserChatSettingsType::class, $userSettings);

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
        if (!$chatSettings) {
            $chatSettings = new ChatSettings();
            $em->persist($chatSettings);
            $em->flush();
        }
        
        $settingsForm = $this->createForm(ChatSettingsType::class, $chatSettings);
        
        return $this->render('ChatBundle:Default:indexAdmin.html.twig', array(
                    'local' => $local,
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'userType' => $userType,
                    'lastConversations' => $lastConversations,
                    'allConversations' => $allConversations,
                    'userSettings' => $userSettings,
                    'userSettingsForm' => $userSettingsForm->createView(),
                    'settingsForm' => $settingsForm->createView(),
        ));
    }

    /**
     * Permite obtener el listado de mensajes que no ha leido un cliente
     * @param Request $request
     */
    public function getClientUnreadMessagesAction(Request $request) {
        $nickname = $request->request->get('nickname');
        $userId = $request->request->get('userId');

        $em = $this->getDoctrine()->getManager();

        $unreadMessages = $em->getRepository('ChatBundle:Message')->findClientUnreadMessages($nickname, $userId);
        
        $arrayUnread = array();

        for($i = 0; $i < count($unreadMessages); $i++) {
            $message = array(
                'msg_date' => $unreadMessages[$i]->getDate()->format('m/d/Y h:i a'),
                'msg' => $unreadMessages[$i]->getMessage(),
                'nickname' => $unreadMessages[$i]->getSenderNickname(),
            );
            array_push($arrayUnread, $message);
        }

        $unreadMessages = json_encode($arrayUnread, true);

        $response = array(
            'result' => '__OK__',
            'messages' => $unreadMessages
        );
        return new JsonResponse($response);
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
            //$process = new Process('./start-gos.sh &');
            $process = new Process('php ../app/console gos:websocket:server&');
            $process->run();
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
