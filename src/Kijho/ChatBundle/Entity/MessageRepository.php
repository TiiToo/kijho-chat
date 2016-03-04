<?php

namespace Kijho\ChatBundle\Entity;

use Doctrine\ORM\EntityRepository;

class MessageRepository extends EntityRepository {

    /**
     * Permite encontrar los dias asignados a un Sprint que se encuentran
     * por fuera del rango de fechas del Sprint
     * @param string $sprintId identificador del Sprint
     * @param type $startDate
     * @param type $endDate
     * @return type
     */
    public function findDaysOutOfRange($sprintId, $startDate, $endDate) {

        $em = $this->getEntityManager();
        $consult = $em->createQuery("
        SELECT spd
        FROM BackendBundle:SprintDay spd
        WHERE spd.sprint = :sprintId
        AND (spd.date < :startDate OR spd.date > :endDate)");
        $consult->setParameter('sprintId', $sprintId);
        $consult->setParameter('startDate', $startDate);
        $consult->setParameter('endDate', $endDate);

        return $consult->getResult();
    }

}
