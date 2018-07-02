<?php

namespace HealthCareAbroad\UserBundle\Repository;

use HealthCareAbroad\UserBundle\Entity\AdminUserType;

use Doctrine\ORM\EntityRepository;

/**
 * AdminUserTypeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AdminUserTypeRepository extends EntityRepository
{
    /**
     * Get all admin user types that are editable
     */
    public function getAllEditable()
    {
        $dql = "SELECT a FROM UserBundle:AdminUserType a WHERE a.status = :active OR a.status = :inactive";
        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameter('active', AdminUserType::STATUS_ACTIVE)
            ->setParameter('inactive', AdminUserType::STATUS_INACTIVE);
        return $query->getResult();
    }
    
    public function getAllActive()
    {
        
    }
}