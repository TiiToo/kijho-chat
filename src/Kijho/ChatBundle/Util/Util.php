<?php

namespace Kijho\ChatBundle\Util;

/**
 * Esta clase contiene funciones comunes usadas en diferentes partes del proyecto
 * @author Cesar Giraldo <cesargiraldo1108@gmail.com> 04/03/2016
 */
class Util {

    public static function getCurrentDate($zone = 'America/Bogota') {
        $timezone = new \DateTimeZone($zone);
        $datetime = new \DateTime('now');
        $datetime->setTimezone($timezone);
        return $datetime;
    }

}
