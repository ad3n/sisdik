<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Staf
 *
 * @ORM\Table(name="staf")
 * @ORM\Entity
 */
class Staf
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $namaLengkap
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     */
    private $namaLengkap;

    /**
     * @var string $username
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsekolah", referencedColumnName="id")
     * })
     */
    private $idsekolah;



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
     * Set namaLengkap
     *
     * @param string $namaLengkap
     * @return Staf
     */
    public function setNamaLengkap($namaLengkap)
    {
        $this->namaLengkap = $namaLengkap;
    
        return $this;
    }

    /**
     * Get namaLengkap
     *
     * @return string 
     */
    public function getNamaLengkap()
    {
        return $this->namaLengkap;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Staf
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Staf
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null)
    {
        $this->idsekolah = $idsekolah;
    
        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }
}