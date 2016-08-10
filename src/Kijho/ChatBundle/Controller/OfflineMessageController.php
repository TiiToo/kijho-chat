<?php

namespace Kijho\ChatBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Kijho\ChatBundle\Entity as Entity;

class OfflineMessageController extends Controller {

    /**
     * Permite almacenar y enviar un correo con un mensaje offline,
     * en caso de que el servidor del chat este apagado o no haya ningun
     * administrador online
     * @param Request $request
     */
    public function sendOfflineMessageAction(Request $request) {

        $nickname = trim(strip_tags($request->request->get('nickname')));
        $userId = trim(strip_tags($request->request->get('userId')));
        $email = trim(strip_tags($request->request->get('email')));
        $subject = trim(strip_tags($request->request->get('subject')));
        $message = trim(strip_tags($request->request->get('message')));

        $emailHost = explode('@', $email);
        //metodo para validar si el host de correo existe
        $verify = checkdnsrr($emailHost[1], 'MX');
        $em = $this->getDoctrine()->getManager();

        $chatSettings = $em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
        if($verify) {
            if ($chatSettings instanceof Entity\ChatSettings && !empty($chatSettings->getEmailOfflineMessages())) {
                //guardamos el mensaje como un mensaje offline en BB.DD
                $offlineMessage = new Entity\OfflineMessage();
                $offlineMessage->setMessage($message);
                $offlineMessage->setSenderId($userId);
                $offlineMessage->setSenderNickname($nickname);
                $offlineMessage->setType(Entity\OfflineMessage::TYPE_CLIENT_TO_ADMIN);
                $offlineMessage->setSubject($subject);
                $offlineMessage->setEmail($email);

                $em->persist($offlineMessage);
                $em->flush();

                $mail = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($email)
                        ->setTo($chatSettings->getEmailOfflineMessages())
                        ->setBody($this->renderView(
                                'ChatBundle:Email:contactForm.html.twig', array(
                            'offlineMessage' => $offlineMessage,
                        )), 'text/html');
                $this->container->get('mailer')->send($mail);

                $response = array(
                    'result' => '__OK__',
                    'msg' => $this->container->get('translator')->trans('server.email_send'),
                );
            } else {
                $response = array(
                    'result' => '__KO__',
                    'msg' => $this->container->get('translator')->trans('server.error_sending_message'),
                );
            }
        } else {
            $response = array(
                    'result' => '__KO__',
                    'msg' => $this->container->get('translator')->trans('server.invalid_email'),
                );
        }
        return new JsonResponse($response);
    }

}
