<?php

namespace Kijho\ChatBundle\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Doctrine\ORM\EntityManager;
use Kijho\ChatBundle\Entity as Entity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ChatTopic extends Controller implements TopicInterface, TopicPeriodicTimerInterface {

    /**
     * Constantes para los tipos de usuarios que se conectan al chat
     */
    const USER_CLIENT = 'client';
    const USER_ADMIN = 'admin';

    /**
     * Constantes para los tipos de mensajes que el servidor envia a los usuarios
     */
    const SERVER_ONLINE_USERS = 'online_users_list';
    const SERVER_NEW_CLIENT_CONNECTION = 'new_client_connected';
    const SERVER_CLIENT_LEFT_ROOM = 'client_left_room';
    const SERVER_WELCOME_MESSAGE = 'welcome_message';
    const SERVER_USER_MESSAGE = 'user_message';
    const MESSAGE_FROM_CLIENT = 'message_from_client';
    const SHOW_CLIENT_CONVERSATION = 'show_client_conversation';

    /**
     * Constantes para los tipos de mensajes que los usuarios envian al servidor
     */
    const MESSAGE_TO_ADMIN = 'message_to_admin';
    const MESSAGE_TO_CLIENT = 'message_to_client';
    const GET_CONVERSATION_WITH_CLIENT = 'get_conversation_with_client';

    /**
     * Instancia de la sala principal del chat (Clientes, Administradores, etc)
     */
    protected $chatTopic;

    /**
     * @var TopicPeriodicTimer
     */
    protected $periodicTimer;

    /**
     * Instancia del Entity Manager para acceder a base de datos
     */
    private $em;

    /**
     * Instancia del container para acceder a parametros globales, renderizar templates, etc
     */
    protected $container;

    public function __construct(EntityManager $em, $container) {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * This will receive any Subscription requests for this topic.
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {

        if ($topic->getId() == 'chat/channel') {
            $this->chatTopic = $topic;
            $this->serverLog($connection->nickname . ' (' . $connection->userType . ') se ha conectado al chat');
        }

        if ($connection->userType == self::USER_ADMIN) {

            //Le enviamos a los administradores el listado de usuarios conectados cada 2 segundos
            $topicTimer = $connection->PeriodicTimer;
            $topicTimer->addPeriodicTimer('online_users', 2, function() use ($topic, $connection) {
                $connection->event($topic->getId(), ['msg' => 'Online Users..',
                    'msg_type' => self::SERVER_ONLINE_USERS,
                    'online_users' => $this->getOnlineClientsNicknames()]);
            });
        } elseif ($connection->userType == self::USER_CLIENT) {

            //notificamos a los administradores que un nuevo cliente se conectó
            $administrators = $this->getOnlineAdministrators();
            foreach ($administrators as $adminTopic) {
                $adminTopic->event($topic->getId(), [
                    'msg' => $connection->nickname . " has joined " . $topic->getId(),
                    'msg_type' => self::SERVER_NEW_CLIENT_CONNECTION,
                ]);
            }
        }

        //enviamos un mensaje de bienvenida al usuario
        $connection->event($topic->getId(), [
            'msg' => 'Hi ' . $connection->nickname . ', welcome to chat',
            'msg_type' => self::SERVER_WELCOME_MESSAGE,
        ]);
    }

    /**
     * This will receive any UnSubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {

        if ($connection->userType == self::USER_CLIENT) {
            //notificamos a los administradores que un cliente abandona la sala
            $administrators = $this->getOnlineAdministrators();
            foreach ($administrators as $adminTopic) {
                $adminTopic->event($topic->getId(), [
                    'msg' => $connection->nickname . " has left " . $topic->getId(),
                    'msg_type' => self::SERVER_CLIENT_LEFT_ROOM,
                ]);
            }
        }

        //this will broadcast the message to ALL subscribers of this topic.
        //$topic->broadcast(['msg' => $connection->nickname . " has left " . $topic->getId()]);
    }

    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param $event
     * @param array $exclude
     * @param array $eligible
     * @return mixed|void
     */
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible) {

        if ($topic->getId() == 'chat/channel') {

            if (isset($event['type']) && !empty($event['type'])) {

                if ($event['type'] == self::MESSAGE_TO_ADMIN && isset($event['destination'])) {

                    $adminNickname = $event['destination'];
                    $message = $event['message'];

                    //buscamos al administrador con el nickname para mandarle el mensaje
                    $administrators = $this->getOnlineAdministrators();

                    foreach ($administrators as $adminTopic) {
                        if ($adminTopic->nickname == $adminNickname) {

                            $cliMessage = new Entity\Message();
                            $cliMessage->setMessage($message);
                            $cliMessage->setSenderId($connection->userId);
                            $cliMessage->setSenderNickname($connection->nickname);
                            $cliMessage->setDestinationId($adminTopic->userId);
                            $cliMessage->setDestinationNickname($adminNickname);
                            $cliMessage->setType(Entity\Message::TYPE_CLIENT_TO_ADMIN);

                            $this->em->persist($cliMessage);
                            $this->em->flush();

                            $adminTopic->event($topic->getId(), [
                                'msg_type' => self::MESSAGE_FROM_CLIENT,
                                'msg' => $connection->nickname . " says: " . $message,
                                'sender' => $connection->nickname,
                            ]);
                        }
                    }
                } else if ($event['type'] == self::GET_CONVERSATION_WITH_CLIENT) {

                    if (isset($event['clientId']) && !empty($event['clientId'])) {
                        $clientId = $event['clientId'];

                        $conversation = $this->em->getRepository('ChatBundle:Message')->findConversationClientAdmin($clientId, $connection->userId);

                        $html = $this->renderView('ChatBundle:Conversation:clientAdmin.html.twig', array(
                            'conversation' => $conversation,
                            'userId' => $connection->userId,
                            'opponentId' => $clientId,
                        ));

                        $connection->event($topic->getId(), [
                            'msg_type' => self::SHOW_CLIENT_CONVERSATION,
                            'client_id' => $clientId,
                            'html' => $html,
                        ]);

                        $this->serverLog('se desea cargar la conversacion de ' . $connection->nickname . ' con el cliente ' . $clientId);
                    }
                } else {
                    $this->serverLog('otro tipo de mensaje');
                }
            } else {
                $this->serverLog('no hay tipo');
            }
        } else {
            $this->serverLog('otro canal');
        }
    }

    /**
     * Like RPC is will use to prefix the channel
     * @return string
     */
    public function getName() {
        return 'chat.topic';
    }

    /**
     * @param TopicPeriodicTimer $periodicTimer
     */
    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer) {
        $this->periodicTimer = $periodicTimer;
    }

    /**
     * @param Topic $topic
     *
     * @return array
     */
    public function registerPeriodicTimer(Topic $topic) {

        /* //add
          $this->periodicTimer->addPeriodicTimer($this, 'hello', 2, function() use ($topic) {
          $topic->broadcast(['msg' => 'usuarios conectados']);
          });

          //exist
          $this->periodicTimer->isPeriodicTimerActive($this, 'hello'); // true or false
          //remove
          $this->periodicTimer->cancelPeriodicTimer($this, 'hello'); */
    }

    private function getOnlineAdministrators() {
        $onlineAdministrators = array();
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->userType == self::USER_ADMIN) {
                    array_push($onlineAdministrators, $subscriber);
                }
            }
        }
        return $onlineAdministrators;
    }

    /**
     * Permite obtener un arreglo con los nicknames de los clientes del chat
     * @return array
     */
    private function getOnlineClientsNicknames() {
        $onlineUsers = array();
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->userType == self::USER_CLIENT) {
                    array_push($onlineUsers, $subscriber->nickname);
                }
            }
        }
        return $onlineUsers;
    }

    /**
     * Permite desplegar mensajes en la consola del servidor de websockets
     * @param string $msg
     */
    private function serverLog($msg) {
        echo($msg . PHP_EOL);
    }

}
