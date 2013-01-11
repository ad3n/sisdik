<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogsmsMasuk
 *
 * @ORM\Table(name="logsms_masuk")
 * @ORM\Entity
 */
class LogsmsMasuk
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}