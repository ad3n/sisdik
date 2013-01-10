<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KepulanganSiswa
 *
 * @ORM\Table(name="kepulangan_siswa")
 * @ORM\Entity
 */
class KepulanganSiswa
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
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     */
    private $tanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="jam", type="string", length=10, nullable=true)
     */
    private $jam;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sms_pulang_terproses", type="boolean", nullable=false)
     */
    private $smsPulangTerproses;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsiswa", referencedColumnName="id")
     * })
     */
    private $idsiswa;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idkelas", referencedColumnName="id")
     * })
     */
    private $idkelas;

    /**
     * @var \StatusKehadiranKepulangan
     *
     * @ORM\ManyToOne(targetEntity="StatusKehadiranKepulangan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idstatus_kehadiran_kepulangan", referencedColumnName="id")
     * })
     */
    private $idstatusKehadiranKepulangan;



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
     * Set tanggal
     *
     * @param \DateTime $tanggal
     * @return KepulanganSiswa
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    
        return $this;
    }

    /**
     * Get tanggal
     *
     * @return \DateTime 
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * Set jam
     *
     * @param string $jam
     * @return KepulanganSiswa
     */
    public function setJam($jam)
    {
        $this->jam = $jam;
    
        return $this;
    }

    /**
     * Get jam
     *
     * @return string 
     */
    public function getJam()
    {
        return $this->jam;
    }

    /**
     * Set smsPulangTerproses
     *
     * @param boolean $smsPulangTerproses
     * @return KepulanganSiswa
     */
    public function setSmsPulangTerproses($smsPulangTerproses)
    {
        $this->smsPulangTerproses = $smsPulangTerproses;
    
        return $this;
    }

    /**
     * Get smsPulangTerproses
     *
     * @return boolean 
     */
    public function getSmsPulangTerproses()
    {
        return $this->smsPulangTerproses;
    }

    /**
     * Set idsiswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $idsiswa
     * @return KepulanganSiswa
     */
    public function setIdsiswa(\Fast\SisdikBundle\Entity\Siswa $idsiswa = null)
    {
        $this->idsiswa = $idsiswa;
    
        return $this;
    }

    /**
     * Get idsiswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getIdsiswa()
    {
        return $this->idsiswa;
    }

    /**
     * Set idkelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $idkelas
     * @return KepulanganSiswa
     */
    public function setIdkelas(\Fast\SisdikBundle\Entity\Kelas $idkelas = null)
    {
        $this->idkelas = $idkelas;
    
        return $this;
    }

    /**
     * Get idkelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getIdkelas()
    {
        return $this->idkelas;
    }

    /**
     * Set idstatusKehadiranKepulangan
     *
     * @param \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan
     * @return KepulanganSiswa
     */
    public function setIdstatusKehadiranKepulangan(\Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $idstatusKehadiranKepulangan = null)
    {
        $this->idstatusKehadiranKepulangan = $idstatusKehadiranKepulangan;
    
        return $this;
    }

    /**
     * Get idstatusKehadiranKepulangan
     *
     * @return \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan 
     */
    public function getIdstatusKehadiranKepulangan()
    {
        return $this->idstatusKehadiranKepulangan;
    }
}