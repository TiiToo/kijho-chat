<?php

namespace Kijho\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kijho\ChatBundle\Util\Util;

/**
 * Message
 * @author Cesar Giraldo <cesargiraldo1108@gmail.com> 04/03/2016
 * @ORM\Table(name="chat_message")
 * @ORM\Entity(repositoryClass="Kijho\ChatBundle\Entity\MessageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Message {

    /**
     * Constantes para los tipos de mensajes
     */
    const TYPE_CLIENT_TO_ADMIN = 1;
    const TYPE_ADMIN_TO_CLIENT = 2;
    const TYPE_ADMIN_TO_ADMIN = 3;
    const TYPE_CLIENT_TO_CLIENT = 4;

    /**
     * @ORM\Id
     * @ORM\Column(name="mess_id", type="integer") 
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Fecha y hora en la que se envia el mensaje
     * @ORM\Column(name="mess_date", type="datetime", nullable=true)
     */
    protected $date;

    /**
     * Tipo de mensaje
     * @ORM\Column(name="mess_type", type="string", nullable=true)
     */
    protected $type;

    /**
     * Boolean para saber si el destinatario ya leyó o no el mensaje
     * @ORM\Column(name="mess_readed", type="boolean", nullable=true)
     */
    protected $readed;
    
    /**
     * Fecha y hora en la que el destinatario lee el mensaje
     * @ORM\Column(name="mess_date_readed", type="datetime", nullable=true)
     */
    protected $dateReaded;

    /**
     * Contenido del mensaje
     * @ORM\Column(name="mess_message", type="text", nullable=true)
     */
    protected $message;

    /**
     * Nickname de la persona que envia el mensaje
     * @ORM\Column(name="mess_sender_nickname", type="string", nullable=true)
     */
    protected $senderNickname;

    /**
     * Identificador del usuario que envia el mensaje
     * @ORM\Column(name="mess_sender_id", type="string", nullable=true)
     */
    protected $senderId;

    /**
     * Nickname de la persona a quien se envia el mensaje
     * @ORM\Column(name="mess_destination_nickname", type="string", nullable=true)
     */
    protected $destinationNickname;

    /**
     * Identificador de la persona a quien se envia el mensaje
     * @ORM\Column(name="mess_destination_id", type="string", nullable=true)
     */
    protected $destinationId;

    /**
     * Boolean que permite saber si un mensaje fue robado o no de otra conversacion
     * @ORM\Column(name="mess_is_steal", type="boolean", nullable=true)
     */
    protected $isStealMessage;
    
    /**
     * Boolean que permite saber si un mensaje fue enviado desde un cliente a todos los administradores
     * @ORM\Column(name="mess_is_send_all_admin", type="boolean", nullable=true)
     */
    protected $isSendToAllAdmin;
    
    function getId() {
        return $this->id;
    }

    function getDate() {
        return $this->date;
    }

    function getType() {
        return $this->type;
    }

    function getReaded() {
        return $this->readed;
    }

    function getMessage() {
        return $this->message;
    }

    function getSenderNickname() {
        return $this->senderNickname;
    }

    function getSenderId() {
        return $this->senderId;
    }

    function getDestinationNickname() {
        return $this->destinationNickname;
    }

    function getDestinationId() {
        return $this->destinationId;
    }

    function setDate($date) {
        $this->date = $date;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setReaded($readed) {
        $this->readed = $readed;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setSenderNickname($senderNickname) {
        $this->senderNickname = $senderNickname;
    }

    function setSenderId($senderId) {
        $this->senderId = $senderId;
    }

    function setDestinationNickname($destinationNickname) {
        $this->destinationNickname = $destinationNickname;
    }

    function setDestinationId($destinationId) {
        $this->destinationId = $destinationId;
    }
    
    function getDateReaded() {
        return $this->dateReaded;
    }

    function setDateReaded($dateReaded) {
        $this->dateReaded = $dateReaded;
    }
    
    function getIsStealMessage() {
        return $this->isStealMessage;
    }

    function setIsStealMessage($isStealMessage) {
        $this->isStealMessage = $isStealMessage;
    }
    
    function getIsSendToAllAdmin() {
        return $this->isSendToAllAdmin;
    }

    function setIsSendToAllAdmin($isSendToAllAdmin) {
        $this->isSendToAllAdmin = $isSendToAllAdmin;
    }

    /**
     * Set Page initial status before persisting
     * @ORM\PrePersist
     */
    public function setDefaults() {
        if (null === $this->getDate()) {
            $this->setDate(Util::getCurrentDate());
        }
        if (null === $this->getReaded()) {
            $this->setReaded(false);
        }
        if (null === $this->getIsStealMessage()) {
            $this->setIsStealMessage(false);
        }
        if (null === $this->getIsSendToAllAdmin()) {
            $this->setIsSendToAllAdmin(false);
        }
    }
    
    /**
     * Permite obtener la informacion del mensaje en un arreglo
     * @return type
     */
    public function getArrayData() {
        
        $arrayData = [
            'date' => $this->getDate()->format('d/m/Y'),
            'hour' => $this->getDate()->format('h:i a'),
            'destination_id' => $this->getDestinationId(),
            'destination_nickname' => $this->getDestinationNickname(),
            'message' => $this->getMessage(),
            'sender_id' => $this->getSenderId(),
            'sender_nickname' => $this->getSenderNickname(),
            'type' => $this->getType(),
            'is_readed' => $this->getReaded(),
        ];
        
        return $arrayData;
        
        
        
    }

}
