<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Staf
 *
 * @ORM\Table(name="staf")
 * @ORM\Entity
 */
class Staf
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     */
    private $namaLengkap;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id")
     * })
     */
    private $sekolah;



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
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return Staf
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    
        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }
}