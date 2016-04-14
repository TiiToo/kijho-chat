<?php

namespace Kijho\ChatBundle\RPC;

use Ratchet\ConnectionInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Kijho\ChatBundle\Topic\ChatTopic;

class RPCService implements RpcInterface {

    /**
     * Adds the params together
     *
     * Note: $conn isnt used here, but contains the connection of the person making this request.
     *
     * @param ConnectionInterface $connection
     * @param WampRequest $request
     * @param array $params
     * @return int
     */
    public function updateConnectionData(ConnectionInterface $connection, WampRequest $request, $params) {
        $connection->nickname = 'Guest-' . strip_tags($connection->resourceId);

        if (isset($params['nickname']) && !empty($params['nickname'])) {
            $nickName = trim(strip_tags($params['nickname']));
            $connection->nickname = $nickName;
            $connection->status = ChatTopic::STATUS_ONLINE;
        } else {
            $connection->status = ChatTopic::STATUS_WAITING_NICKNAME;
        }

        $connection->userId = strip_tags($connection->nickname);
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $userId = trim(strip_tags($params['user_id']));
            $connection->userId = $userId;
        }

        $connection->userType = ChatTopic::USER_CLIENT;
        if (isset($params['user_type']) && !empty($params['user_type'])) {
            $userType = trim(strip_tags($params['user_type']));
            $connection->userType = $userType;
        }
        
        //esta variable se usa para determinar con que administrador 
        //esta hablando cada usuario (en especial los los clientes)
        $connection->onlineWithClient = '';
        $connection->onlineWithAdmin = '';
        
        $connection->email = '';
        if (isset($params['email']) && !empty($params['email'])) {
            $email = trim(strip_tags($params['email']));
            $connection->email = $email;
        }

        return array("result" => array_sum($params));
    }

    /**
     * Name of RPC, use for pubsub router (see step3)
     * 
     * @return string
     */
    public function getName() {
        return 'rpc.service';
    }

}
