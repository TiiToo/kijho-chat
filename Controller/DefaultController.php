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
use Kijho\ChatBundle\Form\ConnectionFormType;
use Kijho\ChatBundle\Util\Util;
use Kijho\ChatBundle\Topic\ChatTopic;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DefaultController extends Controller {

    public function clientPanelAction($nickname = null, $userId = '', $userType = '', $email = '', $local = false) {
        $em = $this->getDoctrine()->getManager();

        $nickname = strtolower(trim(strip_tags($nickname)));
        $nickname = str_replace(' ', '_', $nickname);

        if ($nickname != '' && $userId == '') {
            $userId = $nickname;
        } else {
            $userId = strtolower(trim(strip_tags($userId)));
            $userId = str_replace(' ', '_', $userId);
        }

        //buscamos las configuraciones del usuario, sino tiene se las creamos
        $searchUserSettings = array('userId' => $userId, 'userType' => $userType);
        $userSettings = $em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
        if (!$userSettings) {
            if ($userId != '') {
                $userSettings = new UserChatSettings();
                $userSettings->setUserId($userId);
                $userSettings->setUserType($userType);
                $userSettings->setStatus(ChatTopic::STATUS_ONLINE);
                $em->persist($userSettings);
                $em->flush();
            } else {
                $userSettings = new UserChatSettings();
            }
        }

        $userSettingsForm = $this->createForm(UserChatSettingsType::class, $userSettings, array('translator' => $this->get('translator')));
        $contactForm = $this->createForm(ContactFormType::class, null, array('translator' => $this->get('translator')));
        $connectionForm = $this->createForm(ConnectionFormType::class, null, array('translator' => $this->get('translator')));


        return $this->render('ChatBundle:Default:indexClient.html.twig', array(
                    'local' => $local,
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'email' => $email,
                    'userType' => $userType,
                    'userSettings' => $userSettings,
                    'userSettingsForm' => $userSettingsForm->createView(),
                    'contactForm' => $contactForm->createView(),
                    'connectionForm' => $connectionForm->createView(),
        ));
    }

    public function exampleClientAction() {
        return $this->render('ChatBundle:Default:exampleClient.html.twig');
    }

    public function adminPanelAction($nickname = null, $userId = '', $userType = '', $email = '', $local = false) {

        $nickname = strtolower(trim(strip_tags($nickname)));
        $nickname = str_replace(' ', '_', $nickname);

        if ($nickname != '' && $userId == '') {
            $userId = $nickname;
        } else {
            $userId = strtolower(trim(strip_tags($userId)));
            $userId = str_replace(' ', '_', $userId);
        }

        $em = $this->getDoctrine()->getManager();

        // listado del ultimo mensaje de los clientes para el dia actual
        $lastConversations = $em->getRepository('ChatBundle:Message')->findTodayClientChatNickNames();

        // listado de los nombres de los clientes que han hablado con el administrador
        $adminConversations = $em->getRepository('ChatBundle:Message')->findClientChatNickNames($userId);

        //buscamos las configuraciones del usuario, sino tiene se las creamos
        $searchUserSettings = array('userId' => $userId, 'userType' => $userType);
        $userSettings = $em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
        if (!$userSettings) {
            $userSettings = new UserChatSettings();
            $userSettings->setUserId($userId);
            $userSettings->setUserType($userType);
            $userSettings->setStatus(ChatTopic::STATUS_ONLINE);
            $em->persist($userSettings);
            $em->flush();
        }

        $userSettingsForm = $this->createForm(UserChatSettingsType::class, $userSettings, array('translator' => $this->get('translator')));

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
        if (!$chatSettings) {
            $chatSettings = new ChatSettings();
            $em->persist($chatSettings);
            $em->flush();
        }

        $customMessages = json_decode($chatSettings->getCustomMessages());

//        \Symfony\Component\VarDumper\VarDumper::dump($chatSettings);
        $settingsForm = $this->createForm(ChatSettingsType::class, $chatSettings, array('translator' => $this->get('translator')));

        return $this->render('ChatBundle:Default:indexAdmin.html.twig', array(
                    'local' => $local,
                    'nickname' => $nickname,
                    'userId' => $userId,
                    'email' => $email,
                    'userType' => $userType,
                    'lastConversations' => $lastConversations,
                    'adminConversations' => $adminConversations,
                    'userSettings' => $userSettings,
                    'userSettingsForm' => $userSettingsForm->createView(),
                    'chatSettings' => $chatSettings,
                    'customMessages' => $customMessages,
                    'settingsForm' => $settingsForm->createView(),
        ));
    }

    /**
     * Permite obtener el listado de mensajes de un cliente
     * @param Request $request
     */
    public function getClientMessagesAction(Request $request) {
        $nickname = $request->request->get('nickname');
        $userId = $request->request->get('userId');

        $em = $this->getDoctrine()->getManager();

        $unreadMessages = $em->getRepository('ChatBundle:Message')->findClientUnreadMessages($nickname, $userId);
        $todayMessages = $em->getRepository('ChatBundle:Message')->findClientMessagesFromDate($userId, Util::getCurrentStartDate(), false);

        $arrayMessages = array();

        for ($i = 0; $i < count($todayMessages); $i++) {
            $message = array(
                'msg_date' => $todayMessages[$i]['date']->format('m/d/Y h:i a'),
                'msg' => $todayMessages[$i]['message'],
                'nickname' => $todayMessages[$i]['senderNickname'],
                'type' => $todayMessages[$i]['type'],
                'sender_id' => $todayMessages[$i]['senderId'],
                'destination_id' => $todayMessages[$i]['destinationId']
            );
            array_push($arrayMessages, $message);
        }

        $todayMessages = json_encode($arrayMessages, true);

        $response = array(
            'result' => '__OK__',
            'messages' => $todayMessages,
            'unreadCounter' => count($unreadMessages),
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


        $em = $this->getDoctrine()->getManager();

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
        $chatSettings->setIsRunCommand(ChatSettings::CHAT_START);
        $em->persist($chatSettings);
        $em->flush();
        $response['msg'] = 'waiting crontask run';


        return new JsonResponse($response);
    }

    /**
     * Permite detener la ejecucion del servidor del chat
     * @return JsonResponse
     */
    public function stopGosServerAction() {

        $response = array(
            'result' => '__OK__',
            'msg' => 'Server Stopped...'
        );


        $em = $this->getDoctrine()->getManager();

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
        $chatSettings->setIsRunCommand(ChatSettings::CHAT_STOP);
        $em->persist($chatSettings);
        $em->flush();

        return new JsonResponse($response);
    }

}
