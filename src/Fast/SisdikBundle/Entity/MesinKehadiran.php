<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\MesinKehadiran
 *
 * @ORM\Table(name="mesin_kehadiran")
 * @ORM\Entity
 */
class MesinKehadiran
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
     * @var string $alamatIp
     *
     * @ORM\Column(name="alamat_ip", type="string", length=45, nullable=false)
     */
    private $alamatIp;

    /**
     * @var string $commkey
     *
     * @ORM\Column(name="commkey", type="string", length=45, nullable=false)
     */
    private $commkey;

    /**
     * @var boolean $aktif
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false)
     */
    private $aktif;

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
     * Set alamatIp
     *
     * @param string $alamatIp
     * @return MesinKehadiran
     */
    public function setAlamatIp($alamatIp)
    {
        $this->alamatIp = $alamatIp;
    
        return $this;
    }

    /**
     * Get alamatIp
     *
     * @return string 
     */
    public function getAlamatIp()
    {
        return $this->alamatIp;
    }

    /**
     * Set commkey
     *
     * @param string $commkey
     * @return MesinKehadiran
     */
    public function setCommkey($commkey)
    {
        $this->commkey = $commkey;
    
        return $this;
    }

    /**
     * Get commkey
     *
     * @return string 
     */
    public function getCommkey()
    {
        return $this->commkey;
    }

    /**
     * Set aktif
     *
     * @param boolean $aktif
     * @return MesinKehadiran
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    
        return $this;
    }

    /**
     * Get aktif
     *
     * @return boolean 
     */
    public function getAktif()
    {
        return $this->aktif;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return MesinKehadiran
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