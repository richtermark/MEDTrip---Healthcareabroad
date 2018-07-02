<?php

namespace HealthCareAbroad\StatisticsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InstitutionStatisticsAnnualIpAddresses
 */
class InstitutionStatisticsAnnualIpAddresses
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $ipAddresses;


    /**
     * Set id
     *
     * @param integer $id
     * @return InstitutionStatisticsAnnualIpAddresses
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ipAddresses
     *
     * @param string $ipAddresses
     * @return InstitutionStatisticsAnnualIpAddresses
     */
    public function setIpAddresses($ipAddresses)
    {
        $this->ipAddresses = $ipAddresses;
    
        return $this;
    }

    /**
     * Get ipAddresses
     *
     * @return string 
     */
    public function getIpAddresses()
    {
        return $this->ipAddresses;
    }
}
