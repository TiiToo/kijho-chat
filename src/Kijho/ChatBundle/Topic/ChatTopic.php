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
use Kijho\ChatBundle\Util\Util;

class ChatTopic extends Controller implements TopicInterface, TopicPeriodicTimerInterface {

    /**
     * Constantes para los tipos de usuarios que se conectan al chat
     */
    const USER_CLIENT = 'client';
    const USER_ADMIN = 'admin';
    
    /**
     * Canal por defecto del chat
     */
    const CHAT_CHANNEL = "chat/channel";

    /**
     * Constantes para los tipos de mensajes que el servidor envia a los usuarios
     */
    const SERVER_ONLINE_USERS = 'online_users_list';
    const SERVER_NEW_CLIENT_CONNECTION = 'new_client_connected';
    const SERVER_CLIENT_LEFT_ROOM = 'client_left_room';
    const SERVER_WELCOME_MESSAGE = 'welcome_message';
    const MESSAGE_FROM_CLIENT = 'message_from_client';
    const MESSAGE_TO_CLIENT = 'message_to_client';
    const MESSAGE_FROM_ADMIN = 'message_from_admin';
    const MESSAGE_SEND_SUCCESSFULLY = 'message_send_successfully';

    /**
     * Constantes para los tipos de mensajes que los usuarios envian al servidor
     */
    const MESSAGE_TO_ADMIN = 'message_to_admin';
    const PUT_MESSAGES_AS_READED = 'put_messages_as_readed';

    /**
     * Constante que controla el tiempo en el cual se actualiza el listado de usuarios
     * conectados para el panel de usuarios online
     */
    const TIME_REFRESH_ONLINE_USERS = 5;

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
            $topicTimer->addPeriodicTimer('online_users', self::TIME_REFRESH_ONLINE_USERS, function() use ($topic, $connection) {
                $connection->event($topic->getId(), ['msg' => 'Online Users..',
                    'msg_type' => self::SERVER_ONLINE_USERS,
                    'online_users' => $this->getOnlineClientsData()]);
            });
        } elseif ($connection->userType == self::USER_CLIENT) {

            //notificamos a los administradores que un nuevo cliente se conectó
            $administrators = $this->getOnlineAdministrators();
            foreach ($administrators as $adminTopic) {
                $adminTopic->event($topic->getId(), [
                    'msg_type' => self::SERVER_NEW_CLIENT_CONNECTION,
                    'msg' => $connection->nickname . " has joined " . $topic->getId(),
                    'user_id' => $connection->userId,
                    'nickname' => $connection->nickname,
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
                    'msg_type' => self::SERVER_CLIENT_LEFT_ROOM,
                    'msg' => $connection->nickname . " has left " . $topic->getId(),
                    'user_id' => $connection->userId,
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

                    $messageSaved = false;
                    foreach ($administrators as $adminTopic) {
                        if ($adminTopic->nickname == $adminNickname) {

                            //se utiliza la variable $messageSaved, para solo guardar un mensaje 
                            //y enviarlo a todos los dispositivos del usuario
                            if (!$messageSaved) {
                                $cliMessage = new Entity\Message();
                                $cliMessage->setMessage($message);
                                $cliMessage->setSenderId($connection->userId);
                                $cliMessage->setSenderNickname($connection->nickname);
                                $cliMessage->setDestinationId($adminTopic->userId);
                                $cliMessage->setDestinationNickname($adminNickname);
                                $cliMessage->setType(Entity\Message::TYPE_CLIENT_TO_ADMIN);

                                $this->em->persist($cliMessage);
                                $this->em->flush();
                                $messageSaved = true;
                            }

                            $adminTopic->event($topic->getId(), [
                                'msg_type' => self::MESSAGE_FROM_CLIENT,
                                'msg' => $connection->nickname . " says: " . $message,
                                'sender' => $connection->nickname,
                            ]);
                        }
                    }

                    //notificamos al usuario que su mensaje se envio exitosamente
                    $connection->event($topic->getId(), [
                        'msg' => $connection->nickname . " says: " . $message,
                        'msg_type' => self::MESSAGE_SEND_SUCCESSFULLY,
                    ]);
                } elseif ($event['type'] == self::PUT_MESSAGES_AS_READED) {

                    if (isset($event['clientId']) && !empty($event['clientId'])) {
                        $clientId = $event['clientId'];

                        //buscamos los mensajes que el cliente le ha enviado al administrador
                        $search = array('senderId' => $clientId, 'destinationId' => $connection->userId, 'readed' => false);
                        $conversation = $this->em->getRepository('ChatBundle:Message')->findBy($search);

                        $currentDate = Util::getCurrentDate();
                        foreach ($conversation as $message) {
                            $message->setReaded(true);
                            $message->setDateReaded($currentDate);
                            $this->em->persist($message);
                            $this->em->flush();
                        }
                    }
                } elseif ($event['type'] == self::MESSAGE_TO_CLIENT) {
                    if (isset($event['clientId']) && !empty($event['clientId'])) {
                        $clientId = $event['clientId'];

                        //buscamos la conexion del cliente para enviarle el mensaje
                        $message = $event['message'];

                        //buscamos al administrador con el nickname para mandarle el mensaje
                        $clients = $this->getOnlineClients();

                        $messageSaved = false;
                        foreach ($clients as $clientTopic) {
                            if ($clientTopic->userId == $clientId) {

                                //se utiliza la variable $messageSaved, para solo guardar un mensaje 
                                //y enviarlo a todos los dispositivos del usuario
                                if (!$messageSaved) {
                                    $adminMessage = new Entity\Message();
                                    $adminMessage->setMessage($message);
                                    $adminMessage->setSenderId($connection->userId);
                                    $adminMessage->setSenderNickname($connection->nickname);
                                    $adminMessage->setDestinationId($clientTopic->userId);
                                    $adminMessage->setDestinationNickname($clientTopic->nickname);
                                    $adminMessage->setType(Entity\Message::TYPE_ADMIN_TO_CLIENT);

                                    $this->em->persist($adminMessage);
                                    $this->em->flush();
                                    $messageSaved = true;
                                }

                                $clientTopic->event($topic->getId(), [
                                    'msg_type' => self::MESSAGE_FROM_ADMIN,
                                    'msg' => $connection->nickname . " says: " . $message,
                                    'sender' => $connection->nickname,
                                ]);
                            }
                        }

                        //notificamos al administrador que su mensaje se envio exitosamente
                        $connection->event($topic->getId(), [
                            'msg' => $connection->nickname . " says: " . $message,
                            'msg_type' => self::MESSAGE_SEND_SUCCESSFULLY,
                        ]);
                    }
                } else {
                    $this->serverLog('otro tipo de mensaje');
                }
            } else {
                $this->serverLog('no hay tipo');
            }
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

    /**
     * Permite obtener el listado de los administradores online
     * @author Cesar Giraldo <cnaranjo@kijho.com> 02/03/2016
     * @return array
     */
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
     * Permite obtener el listado de los clientes online
     * @author Cesar Giraldo <cnaranjo@kijho.com> 08/03/2016
     * @return array
     */
    private function getOnlineClients() {
        $onlineClients = array();
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->userType == self::USER_CLIENT) {
                    array_push($onlineClients, $subscriber);
                }
            }
        }
        return $onlineClients;
    }

    /**
     * Permite obtener un arreglo con los nicknames de los clientes del chat
     * @return array
     */
    private function getOnlineClientsData() {
        $onlineUsers = array();
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->userType == self::USER_CLIENT) {
                    $data = array('nickname' => $subscriber->nickname, 'user_id' => $subscriber->userId);
                    if (!in_array($data, $onlineUsers)) {
                        array_push($onlineUsers, $data);
                    }
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
