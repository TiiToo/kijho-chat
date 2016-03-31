<?php

namespace Kijho\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Chat Settings
 * @author Cesar Giraldo <cesargiraldo1108@gmail.com> 31/03/2016
 * @ORM\Table(name="chat_settings")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ChatSettings {


    /**
     * @ORM\Id
     * @ORM\Column(name="cset_id", type="integer") 
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Dirreccion en donde seran enviados los correos con los mensajes de
     * los clientes cuando no hay administradores online
     * @ORM\Column(name="uset_email_offline", type="string", nullable=true)
     */
    protected $emailOfflineMessages;

    function getId() {
        return $this->id;
    }

    function getEmailOfflineMessages() {
        return $this->emailOfflineMessages;
    }

    function setEmailOfflineMessages($emailOfflineMessages) {
        $this->emailOfflineMessages = $emailOfflineMessages;
    }

    /**
     * Set Page initial status before persisting
     * @ORM\PrePersist
     */
    public function setDefaults() {
        
    }
}
