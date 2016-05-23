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
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChatTopic extends Controller implements TopicInterface, TopicPeriodicTimerInterface {

    /**
     * Constantes para los tipos de usuarios que se conectan al chat
     */
    const USER_CLIENT = 'client';
    const USER_ADMIN = 'admin';

    /**
     * Constantes para los posibles estados de los usuarios del chat
     */
    const STATUS_WAITING_NICKNAME = 'waiting-nickname';
    const STATUS_ONLINE = 'is-online';
    const STATUS_BUSY = 'is-busy';
    const STATUS_IDLE = 'is-idle';
    const STATUS_OFFLINE = 'is-offline';

    /**
     * Canal por defecto del chat
     */
    const CHAT_CHANNEL = "chat/channel";

    /**
     * Constante cuando un cliente envia un mensaje a todos los administradores
     */
    const MESSAGE_ALL_ADMINISTRATORS = 'message_all_admin';

    /**
     * Constantes para los tipos de mensajes que el servidor envia a los usuarios
     */
    const NICKNAME_REQUIRED = 'nickname_required';
    const SERVER_ONLINE_USERS = 'online_users_list';
    const SERVER_NEW_CLIENT_CONNECTION = 'new_client_connected';
    const SERVER_CLIENT_LEFT_ROOM = 'client_left_room';
    const SERVER_WELCOME_MESSAGE = 'welcome_message';
    const MESSAGE_FROM_CLIENT = 'message_from_client';
    const MESSAGE_TO_CLIENT = 'message_to_client';
    const MESSAGE_FROM_ADMIN = 'message_from_admin';
    const MESSAGE_SEND_SUCCESSFULLY = 'message_send_successfully';
    const CLIENT_TYPING = 'client_typing';
    const ADMIN_TYPING = 'admin_typing';
    const CLIENT_MESSAGES_PUT_AS_READED = 'client_messages_put_as_readed';
    const SETTINGS_UPDATED = 'settings_updated';
    const SELF_STATUS_UPDATED = 'self_status_updated';
    const CLIENT_STATUS_UPDATED = 'client_status_updated';
    const JOIN_LEFT_ADMIN_TO_ROOM = 'join_left_admin_to_room';
    const CLIENT_AUTOMATIC_MESSAGE = 'client_automatic_message';
    const MESSAGES_FROM_OTHER_CONVERSATION = 'messages_from_other_conversation';
    const WRONG_CONNECTION_DATA = 'wrong_connection_data';
    const NICKNAME_REPEATED = 'nickname_repeated';

    /**
     * Constantes para los tipos de mensajes que los usuarios envian al servidor
     */
    const MESSAGE_TO_ADMIN = 'message_to_admin';
    const PUT_MESSAGES_AS_READED = 'put_messages_as_readed';
    const UPDATE_SETTINGS = 'update_settings';
    const CHANGE_ADMIN_STATUS = 'change_admin_status';
    const CHANGE_CLIENT_STATUS = 'change_client_status';
    const STEAL_CONVERSATION_WITH_CLIENT = 'steal_conversation_with_client';
    const CONNECT_TO_CHAT = 'connect_to_chat';
    const UPDATE_CLIENT_DESTINATION = 'update_client_destination';

    /**
     * Constante que controla el tiempo en el cual se actualiza el listado de usuarios
     * conectados para el panel de usuarios online
     */
    const TIME_REFRESH_ONLINE_USERS = 3;

    /**
     * Constante que controla el tiempo en el cual se envia a los clientes el numero
     * de administradores conectados
     */
    const TIME_REFRESH_ONLINE_ADMINISTRATORS = 2;

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

    /**
     * Esta variable contendra la instancia del traductor
     * @var type 
     */
    protected $translator;

    public function __construct(EntityManager $em, ContainerInterface $container) {
        $this->em = $em;
        $this->container = $container;
        $this->translator = $this->container->get('translator');
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
            $this->serverLog($connection->nickname . ' (' . $connection->userType . ') ' . $this->translator->trans('server.user_connected'));
        }

        if ($connection->userType == self::USER_ADMIN) {

            
            //buscamos las configuraciones del cliente para setear su status
            $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
            $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
            if ($userSettings instanceof Entity\UserChatSettings) {
                $connection->status = $userSettings->getStatus();
            }
            
            //Le enviamos a los administradores el listado de usuarios conectados cada 2 segundos
            $topicTimer = $connection->PeriodicTimer;
            $topicTimer->addPeriodicTimer('online_users', self::TIME_REFRESH_ONLINE_USERS, function() use ($topic, $connection) {
                $connection->event($topic->getId(), ['msg' => 'Online Users..',
                    'msg_type' => self::SERVER_ONLINE_USERS,
                    'online_users' => $this->getOnlineClientsData()]);
            });

            //buscamos las configuraciones del cliente para setear su status
            $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
            $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
            if ($userSettings instanceof Entity\UserChatSettings) {
                //$connection->status = $userSettings->getStatus();
                if (!empty($connection->email)) {
                    $userSettings->setUserEmail($connection->email);
                }
                $userSettings->setIsAnonymousConnection($connection->isAnonymous);
                $this->em->persist($userSettings);
                $this->em->flush();
            }
        } elseif ($connection->userType == self::USER_CLIENT) {

            //buscamos las configuraciones del cliente para setear su status
            $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
            $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);
            if ($userSettings instanceof Entity\UserChatSettings) {
                $connection->status = $userSettings->getStatus();
            }

            if ($connection->status != self::STATUS_WAITING_NICKNAME) {
                //notificamos a los administradores que un nuevo cliente se conectó
                $administrators = $this->getOnlineAdministrators();
                foreach ($administrators as $adminTopic) {
                    $adminTopic->event($topic->getId(), [
                        'msg_type' => self::SERVER_NEW_CLIENT_CONNECTION,
                        'msg' => $connection->nickname . $this->translator->trans('server.has_joined') . $topic->getId(),
                        'user_id' => $connection->userId,
                        'nickname' => $connection->nickname,
                        'status' => $connection->status,
                    ]);
                }

                if ($userSettings instanceof Entity\UserChatSettings) {
                    if (!empty($connection->email)) {
                        $userSettings->setUserEmail($connection->email);
                    }
                    $userSettings->setIsAnonymousConnection($connection->isAnonymous);
                    $this->em->persist($userSettings);
                    $this->em->flush();
                }
            }

            $topicTimer = $connection->PeriodicTimer;
            $topicTimer->addPeriodicTimer('online_administrators', self::TIME_REFRESH_ONLINE_ADMINISTRATORS, function() use ($topic, $connection) {
                $connection->event($topic->getId(), ['msg' => $this->translator->trans('server.online_administrators'),
                    'msg_type' => self::JOIN_LEFT_ADMIN_TO_ROOM,
                    'online_administrators' => $this->getOnlineAdministratorsData()]);
            });

            if ($connection->status != self::STATUS_WAITING_NICKNAME) {
                $connection->event($topic->getId(), ['msg' => '',
                    'msg_type' => self::SERVER_WELCOME_MESSAGE,
                    'online_administrators' => count($this->getOnlineAdministrators()),
                    'anonymous' => false,
                ]);
            }
        }

        if ($connection->status != self::STATUS_WAITING_NICKNAME) {
            //enviamos un mensaje de bienvenida al usuario
            $connection->event($topic->getId(), [
                'msg' => $this->translator->trans('server.hi') . $connection->nickname . ', ' . $this->translator->trans('server.welcome_to_chat'),
                'msg_type' => self::SERVER_WELCOME_MESSAGE,
                'anonymous' => false,
            ]);
        } else {
            // indicamos al usuario que debe asignar un nickname para el chat
            $connection->event($topic->getId(), [
                'msg' => 'Nickname required',
                'msg_type' => self::NICKNAME_REQUIRED,
            ]);
        }
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
                    'msg' => $connection->nickname . $this->translator->trans('server.has_left') . $topic->getId(),
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

                $eventType = trim(strip_tags($event['type']));

                if ($connection->userType == self::USER_ADMIN) {
                    if ($eventType == self::PUT_MESSAGES_AS_READED) {

                        if (isset($event['clientId']) && !empty($event['clientId'])) {
                            $clientId = trim(strip_tags($event['clientId']));

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
                    } elseif ($eventType == self::ADMIN_TYPING) {
                        if (isset($event['clientId']) && !empty($event['clientId'])) {
                            $clientId = trim(strip_tags($event['clientId']));

                            //buscamos al administrador con el nickname para mandarle el mensaje
                            $clients = $this->getOnlineClients();
                            $foundClient = null;
                            foreach ($clients as $clientTopic) {
                                if ($clientTopic->userId == $clientId) {
                                    $clientTopic->event($topic->getId(), [
                                        'msg_type' => self::ADMIN_TYPING,
                                        'msg' => $connection->nickname . $this->translator->trans('server.is_typing'),
                                    ]);
                                }
                            }
                        }
                    } elseif ($eventType == self::MESSAGE_TO_CLIENT) {
                        if (isset($event['clientId']) && !empty($event['clientId'])) {
                            $clientId = trim(strip_tags($event['clientId']));

                            //buscamos la conexion del cliente para enviarle el mensaje
                            $message = trim(strip_tags($event['message']));

                            //buscamos al administrador con el nickname para mandarle el mensaje
                            $clients = $this->getOnlineClients();
                            $foundClient = null;
                            $messageSaved = false;
                            $adminMessage = null;
                            $notifyOtherAdmins = false;
                            foreach ($clients as $clientTopic) {
                                if ($clientTopic->userId == $clientId) {

                                    //indicamos que el admin esta hablando con el cliente
                                    $connection->onlineWithClient = $clientTopic->nickname;

                                    $foundClient = $clientTopic;

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

                                    //indicamos que el cliente esta hablando con el admin
                                    if ($clientTopic->onlineWithAdmin != $connection->nickname) {
                                        $notifyOtherAdmins = true;
                                    }
                                    $clientTopic->onlineWithAdmin = $connection->nickname;

                                    $clientTopic->event($topic->getId(), [
                                        'msg_type' => self::MESSAGE_FROM_ADMIN,
                                        'msg' => $message,
                                        'nickname' => $connection->nickname,
                                        'user_id' => $connection->userId,
                                        'msg_date' => $adminMessage->getDate()->format('h:i a'),
                                    ]);
                                }
                            }


                            if ($adminMessage) {
                                //notificamos al administrador que su mensaje se envio exitosamente
                                $connection->event($topic->getId(), [
                                    'msg_type' => self::MESSAGE_SEND_SUCCESSFULLY,
                                    'msg' => $adminMessage->getMessage(),
                                    'client_id' => $adminMessage->getDestinationId(),
                                    'client_nickname' => $adminMessage->getDestinationNickname(),
                                    'msg_date' => $adminMessage->getDate()->format('h:i a'),
                                ]);
                            }

                            /**
                             * Verificamos si tenbemos que notificar a los otros administradores
                             * que el administrador actual ya se hizo cargo de la conversacion
                             */
                            if ($notifyOtherAdmins && $foundClient) {
                                $message = $this->translator->trans('server.automatic_message') . $connection->nickname . $this->translator->trans('server.will_continue_conversation');

                                $administrators = $this->getOnlineAdministrators();
                                foreach ($administrators as $adminTopic) {
                                    if ($adminTopic->userId != $connection->userId) {
                                        $cliMessage = new Entity\Message();
                                        $cliMessage->setMessage($message);
                                        $cliMessage->setSenderId($foundClient->userId);
                                        $cliMessage->setSenderNickname($foundClient->nickname);
                                        $cliMessage->setDestinationId($adminTopic->userId);
                                        $cliMessage->setDestinationNickname($adminTopic->nickname);
                                        $cliMessage->setType(Entity\Message::TYPE_CLIENT_TO_ADMIN);
                                        $cliMessage->setIsAutomaticMessage(true);
                                        $this->em->persist($cliMessage);
                                        $this->em->flush();

                                        $adminTopic->event($topic->getId(), [
                                            'msg_type' => self::MESSAGE_FROM_CLIENT,
                                            'msg' => $message,
                                            'nickname' => $connection->nickname,
                                            'user_id' => $foundClient->userId,
                                            'msg_date' => $cliMessage->getDate()->format('h:i a'),
                                            'admin_destination' => $connection->userId,
                                        ]);
                                    }
                                }
                            }
                        }
                    } elseif ($eventType == self::UPDATE_SETTINGS) {

                        $notificationSound = trim(strip_tags($event['notificationSound']));
                        $emailOfflineMessages = trim(strip_tags($event['emailOfflineMessages']));
                        $automaticWelcomeMessage = trim(strip_tags($event['automaticWelcomeMessage']));
                        $customMessages = (array) $event['customMessages'];
                        $enableCustomMessages = (boolean) trim(strip_tags($event['enableCustomMessages']));

                        $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
                        $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);

                        if ($userSettings instanceof Entity\UserChatSettings) {
                            $userSettings->setNotificationSound($notificationSound);
                            $this->em->persist($userSettings);

                            $settings = $this->em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
                            if ($settings instanceof Entity\ChatSettings) {
                                $settings->setEmailOfflineMessages($emailOfflineMessages);
                                $settings->setEnableCustomResponses($enableCustomMessages);
                                $settings->setCustomMessages(json_encode($customMessages, true));
                                $settings->setAutomaticMessage($automaticWelcomeMessage);
                                $this->em->persist($settings);
                            }

                            $this->em->flush();

                            $htmlCustomMessages = $this->renderView(
                                    'ChatBundle:ChatSettings:customMessagesToSend.html.twig', array(
                                'customMessages' => $customMessages,
                                'fromServer' => true
                            ));

                            //notificamos al administrador que sus configuraciones se actualizaron
                            $connection->event($topic->getId(), [
                                'msg_type' => self::SETTINGS_UPDATED,
                                'msg' => $this->translator->trans('server.settings_updated'),
                                'enableCustomMessages' => $enableCustomMessages,
                                'html_custom_messages' => $htmlCustomMessages,
                            ]);
                        }
                    } else if ($eventType == self::CHANGE_ADMIN_STATUS) {

                        $newStatus = trim(strip_tags($event['newStatus']));
                        $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
                        $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);

                        if ($userSettings instanceof Entity\UserChatSettings) {
                            $previousStatus = $userSettings->getStatus();
                            $userSettings->setStatus($newStatus);
                            $this->em->persist($userSettings);
                            $this->em->flush();

                            $connection->status = $newStatus;

                            //notificamos al administrador que se cambio su estado
                            $connection->event($topic->getId(), [
                                'msg_type' => self::SELF_STATUS_UPDATED,
                                'msg' => $this->translator->trans('server.status_updated'),
                                'previous_status' => $previousStatus,
                                'new_status' => $connection->status,
                            ]);
                        }
                    } else if ($eventType == self::STEAL_CONVERSATION_WITH_CLIENT) {

                        //debemos buscar el cliente con el que se quiere conversar
                        if (isset($event['clientId']) && !empty($event['clientId'])) {
                            $clientId = trim(strip_tags($event['clientId']));

                            //buscamos el cliente
                            //debemos saber con que administrador esta hablando
                            $clients = $this->getOnlineClients();
                            $clientConnection = null;
                            $adminNickname = null;
                            foreach ($clients as $clientTopic) {
                                if ($clientTopic->userId == $clientId) {
                                    $clientConnection = $clientTopic;
                                    $adminNickname = $clientTopic->onlineWithAdmin;
                                    break;
                                }
                            }

                            if ($adminNickname) {


                                //buscamos el administrador con quien esta hablando el cliente
                                $administrators = $this->getOnlineAdministrators();
                                $adminId = null;
                                $previousAdmin = null;
                                foreach ($administrators as $adminTopic) {
                                    if ($adminTopic->nickname == $adminNickname) {
                                        $adminId = $adminTopic->userId;
                                        $previousAdmin = $adminTopic;
                                        break;
                                    }
                                }

                                if ($adminId) {

                                    //debemos consultar el listado de todos los mensajes del dia actual entre ese cliente y ese admin (mensajes no robados)
                                    $startDate = Util::getCurrentStartDate();
                                    $endDate = Util::getCurrentDate();

                                    $this->serverLog($startDate->format('d/m/Y H:i'));
                                    $this->serverLog($endDate->format('d/m/Y H:i'));

                                    //consultamos y duplicamos los mensajes en base de datos, pero guardandolos con el identificador del admin (senderId, destinationId)
                                    $conversation = $this->em->getRepository('ChatBundle:Message')->findConversationClientAdmin($clientId, $adminId, false, false, $startDate, $endDate);
                                    $stealMessages = array();
                                    foreach ($conversation as $message) {
                                        $stealMessage = clone $message;
                                        if ($stealMessage->getType() == Entity\Message::TYPE_CLIENT_TO_ADMIN) {
                                            $stealMessage->setDestinationId($connection->userId);
                                        } elseif ($stealMessage->getType() == Entity\Message::TYPE_ADMIN_TO_CLIENT) {
                                            $stealMessage->setSenderId($connection->userId);
                                        }
                                        $stealMessage->setIsStealMessage(true);
                                        $this->em->persist($stealMessage);
                                        array_push($stealMessages, $stealMessage->getArrayData());
                                    }
                                    $this->em->flush();


                                    //debemos guardar y enviar una notificacion al anterior administrador, para que sepa quien llevara a cabo la conversacion.
                                    if ($previousAdmin && $clientConnection) {
                                        $message = $this->translator->trans('server.automatic_message') . $connection->nickname . $this->translator->trans('server.will_continue_conversation');
                                        $cliMessage = new Entity\Message();
                                        $cliMessage->setMessage($message);
                                        $cliMessage->setSenderId($clientConnection->userId);
                                        $cliMessage->setSenderNickname($clientConnection->nickname);
                                        $cliMessage->setDestinationId($previousAdmin->userId);
                                        $cliMessage->setDestinationNickname($previousAdmin->nickname);
                                        $cliMessage->setType(Entity\Message::TYPE_CLIENT_TO_ADMIN);
                                        $cliMessage->setDate(Util::getCurrentDate());
                                        $cliMessage->setIsAutomaticMessage(true);
                                        $this->em->persist($cliMessage);
                                        $this->em->flush();

                                        $previousAdmin->event($topic->getId(), [
                                            'msg_type' => self::MESSAGE_FROM_CLIENT,
                                            'msg' => $message,
                                            'nickname' => $connection->nickname,
                                            'user_id' => $clientConnection->userId,
                                            'msg_date' => $cliMessage->getDate()->format('h:i a'),
                                            'admin_destination' => $connection->userId,
                                        ]);

                                        $previousAdmin->onlineWithClient = '';
                                    }

                                    //podemos enviar una notificacion al cliente indicando que otro administrador atendera sus mensajes
                                    if ($clientConnection) {
                                        $clientConnection->event($topic->getId(), [
                                            'msg_type' => self::MESSAGE_FROM_ADMIN,
                                            'msg' => $this->translator->trans('server.automatic_message') . $connection->nickname . $this->translator->trans('server.will_continue_conversation_short'),
                                            'nickname' => $connection->nickname,
                                            'user_id' => $connection->userId,
                                            'msg_date' => Util::getCurrentDate()->format('h:i a'),
                                        ]);

                                        //cambiamos las variables onlineWithClient y onlineWithAdmin segun corresponda
                                        $clientConnection->onlineWithAdmin = $connection->nickname;
                                        $connection->onlineWithClient = $clientConnection->nickname;
                                    }

                                    //debemos enviar el listado de mensajes robados al nuevo admin, para que vea la conversacion
                                    $connection->event($topic->getId(), [
                                        'msg_type' => self::MESSAGES_FROM_OTHER_CONVERSATION,
                                        'client_id' => $clientId,
                                        'messages' => $stealMessages,
                                    ]);
                                }
                            }
                        }
                    }
                } elseif ($connection->userType == self::USER_CLIENT) {
                    if ($eventType == self::MESSAGE_TO_ADMIN && isset($event['destination'])) {

                        $sendAutomaticMessage = false;
                        $automaticMessage = '';

                        //debemos consultar si el el primer mensaje del cliente en el dia actual, para enviarle un mensaje automatico
                        $chatSettings = $this->em->getRepository('ChatBundle:ChatSettings')->findOneBy(array(), array());
                        if ($chatSettings instanceof Entity\ChatSettings && !empty($chatSettings->getAutomaticMessage())) {
                            $date = Util::getCurrentStartDate();
                            $clientMessages = $this->em->getRepository('ChatBundle:Message')->findClientMessagesFromDate($connection->userId, $date);
                            if (empty($clientMessages)) {
                                $sendAutomaticMessage = true;
                                $automaticMessage = $chatSettings->getAutomaticMessage();
                            }
                        }


                        $message = trim(strip_tags($event['message']));
                        $messageSaved = false;
                        $administrators = $this->getOnlineAdministrators();

                        $adminId = trim(strip_tags($event['destination']));


                        //buscamos al administrador con el nickname para mandarle el mensaje
                        foreach ($administrators as $adminTopic) {

                            if ($adminId == self::MESSAGE_ALL_ADMINISTRATORS || $adminTopic->userId == $adminId) {

                                //se utiliza la variable $messageSaved, para solo guardar un mensaje 
                                //y enviarlo a todos los dispositivos del usuario
                                if (!$messageSaved || $adminId == self::MESSAGE_ALL_ADMINISTRATORS) {
                                    $cliMessage = new Entity\Message();
                                    $cliMessage->setMessage($message);
                                    $cliMessage->setSenderId($connection->userId);
                                    $cliMessage->setSenderNickname($connection->nickname);
                                    $cliMessage->setDestinationId($adminTopic->userId);
                                    $cliMessage->setDestinationNickname($adminTopic->nickname);
                                    $cliMessage->setType(Entity\Message::TYPE_CLIENT_TO_ADMIN);

                                    if ($adminId == self::MESSAGE_ALL_ADMINISTRATORS) {
                                        $cliMessage->setIsSendToAllAdmin(true);
                                    }

                                    $this->em->persist($cliMessage);
                                    $this->em->flush();
                                    $messageSaved = true;

                                    //indicamos que el cliente esta hablando con el admin
                                    if ($adminId == self::MESSAGE_ALL_ADMINISTRATORS) {
                                        $connection->onlineWithAdmin = $adminId;
                                    } else {
                                        $connection->onlineWithAdmin = $adminTopic->nickname;
                                    }
                                }

                                $adminTopic->event($topic->getId(), [
                                    'msg_type' => self::MESSAGE_FROM_CLIENT,
                                    'msg' => $message,
                                    'nickname' => $connection->nickname,
                                    'user_id' => $connection->userId,
                                    'msg_date' => $cliMessage->getDate()->format('h:i a'),
                                    'admin_destination' => $adminId,
                                ]);
                            }
                        }

                        if ($messageSaved) {

                            //notificamos al usuario que su mensaje se envio exitosamente
                            $connection->event($topic->getId(), [
                                'msg_type' => self::MESSAGE_SEND_SUCCESSFULLY,
                                'msg' => $message,
                                'nickname' => $connection->nickname,
                                'user_id' => $connection->userId,
                                'msg_date' => $cliMessage->getDate()->format('h:i a'),
                            ]);

                            if ($sendAutomaticMessage) {
                                $connection->event($topic->getId(), [
                                    'msg_type' => self::CLIENT_AUTOMATIC_MESSAGE,
                                    'msg' => $automaticMessage,
                                    'nickname' => 'System',
                                    'msg_date' => Util::getCurrentDate()->format('h:i a'),
                                ]);
                            }
                        }
                    } elseif ($eventType == self::CLIENT_TYPING) {

                        //buscamos a los administradores para notificarles que el cliente esta escribiendo
                        $administrators = $this->getOnlineAdministrators();

                        foreach ($administrators as $adminTopic) {
                            $adminTopic->event($topic->getId(), [
                                'msg_type' => self::CLIENT_TYPING,
                                'nickname' => $connection->nickname,
                                'user_id' => $connection->userId,
                                'online_with' => $connection->onlineWithAdmin,
                            ]);
                        }
                    } elseif ($eventType == self::PUT_MESSAGES_AS_READED) {

                        //buscamos los mensajes que el cliente tiene sin leer
                        $unreadMessages = $this->em->getRepository('ChatBundle:Message')->findClientUnreadMessages($connection->nickname, $connection->userId);

                        $currentDate = Util::getCurrentDate();
                        foreach ($unreadMessages as $message) {
                            $message->setReaded(true);
                            $message->setDateReaded($currentDate);
                            $this->em->persist($message);
                            $this->em->flush();
                        }

                        //notificamos al usuario que sus mensajes fueron marcados como leidos
                        $connection->event($topic->getId(), [
                            'msg_type' => self::CLIENT_MESSAGES_PUT_AS_READED,
                        ]);
                    } elseif ($eventType == self::UPDATE_CLIENT_DESTINATION) {
                        $destinationId = trim(strip_tags($event['destination']));

                        $administrators = $this->getOnlineAdministrators();

                        //buscamos al administrador con el id para setear el destino
                        foreach ($administrators as $adminTopic) {
                            if ($adminTopic->userId == $destinationId) {
                                $connection->onlineWithAdmin = $adminTopic->nickname;
                                break;
                            }
                        }
                    } elseif ($eventType == self::UPDATE_SETTINGS) {

                        $notificationSound = trim(strip_tags($event['notificationSound']));

                        $searchUserSettings = array('userId' => $connection->userId, 'userType' => $connection->userType);
                        $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);

                        if (!$userSettings && $connection->userId != '') {
                            $userSettings = new Entity\UserChatSettings();
                            $userSettings->setStatus($connection->status);
                            $userSettings->setUserId($connection->userId);
                            $userSettings->setUserType($connection->userType);
                        }

                        if ($userSettings) {
                            $userSettings->setNotificationSound($notificationSound);
                            $this->em->persist($userSettings);
                            $this->em->flush();

                            //notificamos al usuario que sus configuraciones se actualizaron
                            $connection->event($topic->getId(), [
                                'msg_type' => self::SETTINGS_UPDATED,
                                'msg' => $this->translator->trans('server.settings_updated')
                            ]);
                        }
                    } else if ($eventType == self::CHANGE_CLIENT_STATUS) {

                        $newStatus = trim(strip_tags($event['newStatus']));
                        $searchUserSettings = array('userId' => $connection->userId, 'userType' => Entity\UserChatSettings::TYPE_CLIENT);
                        $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);

                        if ($userSettings instanceof Entity\UserChatSettings) {
                            $previousStatus = $userSettings->getStatus();
                            $userSettings->setStatus($newStatus);
                            $this->em->persist($userSettings);
                            $this->em->flush();

                            $connection->status = $newStatus;

                            //notificamos al cliente que se cambio su estado
                            $connection->event($topic->getId(), [
                                'msg_type' => self::SELF_STATUS_UPDATED,
                                'msg' => $this->translator->trans('server.status_updated'),
                                'previous_status' => $previousStatus,
                                'new_status' => $connection->status,
                            ]);

                            //debemos notificar a todos los administradores el cambio de status del cliente
                            $administrators = $this->getOnlineAdministrators();

                            foreach ($administrators as $adminTopic) {
                                $adminTopic->event($topic->getId(), [
                                    'msg_type' => self::CLIENT_STATUS_UPDATED,
                                    'nickname' => $connection->nickname,
                                    'user_id' => $connection->userId,
                                    'previous_status' => $previousStatus,
                                    'new_status' => $connection->status,
                                ]);
                            }
                        }
                    } elseif ($eventType == self::CONNECT_TO_CHAT) {
                        $email = strtolower(trim(strip_tags($event['email'])));
                        $nickname = strtolower(trim(strip_tags($event['nickname'])));
                        $nickname = str_replace(' ', '_', $nickname);

                        if (!empty($nickname) && !empty($email)) {

                            //buscamos las configuraciones dle usuario, sino tiene se las creamos
                            $searchUserSettings = array('userId' => $nickname, 'userType' => $connection->userType);
                            $userSettings = $this->em->getRepository('ChatBundle:UserChatSettings')->findOneBy($searchUserSettings);

                            if (!$userSettings) {
                                $userSettings = new Entity\UserChatSettings();
                                $userSettings->setUserId($nickname);
                                $userSettings->setUserType($connection->userType);
                                $userSettings->setStatus(self::STATUS_ONLINE);
                                $userSettings->setNotificationSound(Entity\UserChatSettings::DEFAULT_SOUND);
                                $this->em->persist($userSettings);
                                $this->em->flush();
                            }

                            //debemos buscar si el nickname ingresado ya esta online
                            if (!$this->nicknameIsOnline($nickname)) {
                                $connection->nickname = $nickname;
                                $connection->userId = $nickname;
                                $connection->email = $email;
                                $connection->status = self::STATUS_ONLINE;
                                $connection->isAnonymous = true;

                                if ($userSettings instanceof Entity\UserChatSettings) {
                                    $userSettings->setUserEmail($connection->email);
                                    $userSettings->setIsAnonymousConnection($connection->isAnonymous);
                                    $this->em->persist($userSettings);
                                    $this->em->flush();
                                }

                                $connection->event($topic->getId(), [
                                    'msg_type' => self::SERVER_WELCOME_MESSAGE,
                                    'msg' => $this->translator->trans('server.hi') . $connection->nickname . ', ' . $this->translator->trans('server.welcome_to_chat'),
                                    'anonymous' => true,
                                    'nickname' => $connection->nickname,
                                    'email' => $connection->email,
                                    'status' => $userSettings->getStatus(),
                                    'sound' => $userSettings->getNotificationSound(),
                                ]);
                            } else {
                                $connection->event($topic->getId(), [
                                    'msg_type' => self::NICKNAME_REPEATED,
                                    'msg' => $this->translator->trans('connection_form.nickname_repeated'),
                                ]);
                            }
                        } else {
                            //notificamos que ha ingresado mal el email o el nickname
                            $connection->event($topic->getId(), [
                                'msg_type' => self::WRONG_CONNECTION_DATA,
                                'msg' => $this->translator->trans('connection_form.invalid_email_or_nickname'),
                            ]);
                        }
                    }
                }
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
                if ($subscriber->userType == self::USER_ADMIN && $subscriber->status != self::STATUS_OFFLINE) {
                    array_push($onlineAdministrators, $subscriber);
                }
            }
        }
        return $onlineAdministrators;
    }

    /**
     * Permite obtener un arreglo con los datos de los administradores online
     * @author Cesar Giraldo <cnaranjo@kijho.com> 02/03/2016
     * @return array
     */
    private function getOnlineAdministratorsData() {
        $onlineAdministrators = array();
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->userType == self::USER_ADMIN && $subscriber->status != self::STATUS_OFFLINE) {
                    $data = array(
                        'nickname' => $subscriber->nickname,
                        'user_id' => $subscriber->userId,
                        'status' => $subscriber->status,
                        'onlineWithClient' => $subscriber->onlineWithClient
                    );

                    if (!in_array($data, $onlineAdministrators)) {
                        array_push($onlineAdministrators, $data);
                    }
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
                if ($subscriber->userType == self::USER_CLIENT && $subscriber->status != self::STATUS_WAITING_NICKNAME) {
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
                if ($subscriber->userType == self::USER_CLIENT && $subscriber->status != self::STATUS_WAITING_NICKNAME) {
                    $data = array(
                        'nickname' => $subscriber->nickname,
                        'user_id' => $subscriber->userId,
                        'status' => $subscriber->status,
                        'onlineWithAdmin' => $subscriber->onlineWithAdmin,
                        'isAnonymous' => $subscriber->isAnonymous,
                    );
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

    /**
     * Permite comprobar si un nickname existe o no
     * @param string $nickname nickname a comprobar
     * @return boolean
     */
    private function nicknameIsOnline($nickname) {
        $nicknameExists = false;
        if ($this->chatTopic) {
            foreach ($this->chatTopic->getIterator() as $subscriber) {
                if ($subscriber->nickname == $nickname && $subscriber->status != self::STATUS_WAITING_NICKNAME) {
                    $nicknameExists = true;
                    break;
                }
            }
        }
        return $nicknameExists;
    }

}
