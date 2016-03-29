<?php

namespace Kijho\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * User Chat Settings
 * @author Cesar Giraldo <cesargiraldo1108@gmail.com> 28/03/2016
 * @ORM\Table(name="user_chat_settings")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class UserChatSettings {

    const TYPE_ADMIN = 'admin';
    const TYPE_CLIENT = 'client';
    const DEFAULT_SOUND = 'sounds-capisci.mp3';

    /**
     * @ORM\Id
     * @ORM\Column(name="uset_id", type="integer") 
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Contenido del mensaje
     * @ORM\Column(name="uset_notification_sound", type="string", nullable=true)
     */
    protected $notificationSound;
    
    /**
     * Estado del usuario
     * @ORM\Column(name="uset_status", type="string", nullable=true)
     */
    protected $status;

    /**
     * Identificador del usuario que tiene asociadas las configuraciones
     * @ORM\Column(name="uset_user_id", type="string", nullable=true)
     */
    protected $userId;

    /**
     * Tipo del usuario que tiene asociadas las configuraciones (1=Admin, 2=Client)
     * @ORM\Column(name="uset_user_type", type="string", nullable=true)
     */
    protected $userType;

    function getId() {
        return $this->id;
    }

    function getNotificationSound() {
        return $this->notificationSound;
    }

    function getUserId() {
        return $this->userId;
    }

    function getUserType() {
        return $this->userType;
    }

    function setNotificationSound($notificationSound) {
        $this->notificationSound = $notificationSound;
    }

    function setUserId($userId) {
        $this->userId = $userId;
    }

    function setUserType($userType) {
        $this->userType = $userType;
    }

    function getStatus() {
        return $this->status;
    }

    function setStatus($status) {
        $this->status = $status;
    }

    /**
     * Set Page initial status before persisting
     * @ORM\PrePersist
     */
    public function setDefaults() {
        if (null === $this->getNotificationSound()) {
            $this->setNotificationSound(self::DEFAULT_SOUND);
        }
    }

}
