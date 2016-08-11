<?php

namespace Kijho\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Kijho\ChatBundle\Util\Util;

/**
 * Message
 * @author Cesar Giraldo <cesargiraldo1108@gmail.com> 04/03/2016
 * @ORM\Table(name="chat_offline_message")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class OfflineMessage {

    /**
     * Constantes para los tipos de mensajes
     */
    const TYPE_CLIENT_TO_ADMIN = 1;
    const TYPE_ADMIN_TO_CLIENT = 2;
    const TYPE_ADMIN_TO_ADMIN = 3;
    const TYPE_CLIENT_TO_CLIENT = 4;

    /**
     * @ORM\Id
     * @ORM\Column(name="omes_id", type="integer") 
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Fecha y hora en la que se envia el mensaje
     * @ORM\Column(name="omes_date", type="datetime", nullable=true)
     */
    protected $date;

    /**
     * Tipo de mensaje
     * @ORM\Column(name="omes_type", type="string", nullable=true)
     */
    protected $type;

    /**
     * Asunto del mensaje offline
     * @ORM\Column(name="omes_subject", type="string", nullable=true)
     */
    protected $subject;
    
    /**
     * Email del usuario que envia el mensaje offline
     * @Assert\Email(checkMX=true)
     * @ORM\Column(name="omes_email", type="string", nullable=true)
     */
    protected $email;

    /**
     * Contenido del mensaje
     * @ORM\Column(name="omes_message", type="text", nullable=true)
     */
    protected $message;

    /**
     * Nickname de la persona que envia el mensaje
     * @ORM\Column(name="omes_sender_nickname", type="string", nullable=true)
     */
    protected $senderNickname;

    /**
     * Identificador del usuario que envia el mensaje
     * @ORM\Column(name="omes_sender_id", type="string", nullable=true)
     */
    protected $senderId;

    function getId() {
        return $this->id;
    }

    function getDate() {
        return $this->date;
    }

    function getType() {
        return $this->type;
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

    function setDate($date) {
        $this->date = $date;
    }

    function setType($type) {
        $this->type = $type;
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
    
    function getSubject() {
        return $this->subject;
    }

    function getEmail() {
        return $this->email;
    }

    function setSubject($subject) {
        $this->subject = $subject;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Set Page initial status before persisting
     * @ORM\PrePersist
     */
    public function setDefaults() {
        if (null === $this->getDate()) {
            $this->setDate(Util::getCurrentDate());
        }
    }

}
