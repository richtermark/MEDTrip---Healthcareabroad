<?php

namespace HealthCareAbroad\HelperBundle\Repository;

use HealthCareAbroad\HelperBundle\Entity\GlobalAward;

use Doctrine\ORM\EntityRepository;

/**
 * GlobalAwardRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GlobalAwardRepository extends EntityRepository
{

    public function findByIds(array $ids, $loadEager=true)
    {
        if (\count($ids)) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $query = $qb->select('g, ga')
            ->from('HelperBundle:GlobalAward', 'g')
            ->innerJoin('g.awardingBody', 'ga')
            ->where($qb->expr()->in('g.id', ':ids'))
            ->setParameter('ids', $ids)
            ->getQuery();
            
            $result = $query->getResult();
        }
        else {
            $result = array();
        }
        
        return $result;
    }
    
    
	function getInstitutionGlobalAwards($medicalCenterId)
	{
		$conn = $this->_em->getConnection();
		$qry = "SELECT b.id,b.name FROM institution_global_awards AS a " .
						"JOIN global_awards AS b ON a.global_award_id = b.id " .
						"JOIN  institution_medical_centers AS c ON a.institution_medical_center_id = c.id " .
						"WHERE a.institution_medical_center_id = $medicalCenterId and b.status = ". GlobalAward::STATUS_ACTIVE ." ";
	
		$result = $conn->executeQuery($qry)->fetchAll();
	
		return $result;
	}
	
	public function updateGlobalAwards($global_awardId, $institutionMedicalCenterId)
	{
		$conn = $this->_em->getConnection();
		
		$deleteQry = "DELETE FROM institution_global_awards " .
						"WHERE institution_medical_center_id = $institutionMedicalCenterId " .
						"AND global_award_id = " . $global_awardId . " ";
		
		$result = $conn->executeQuery($deleteQry);
		
		return $result;
	}
}