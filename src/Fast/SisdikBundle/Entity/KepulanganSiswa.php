<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KepulanganSiswa
 *
 * @ORM\Table(name="kepulangan_siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="siswa_UNIQUE1", columns={"siswa_id", "tanggal"})
 * })
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
     * @ORM\Column(name="sms_pulang_terproses", type="boolean", nullable=false, options={"default"=0})
     */
    private $smsPulangTerproses;

    /**
     * @var \Kelas
     *
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $kelas;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * @var \StatusKehadiranKepulangan
     *
     * @ORM\ManyToOne(targetEntity="StatusKehadiranKepulangan")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_kehadiran_kepulangan_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $statusKehadiranKepulangan;



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
     * Set kelas
     *
     * @param \Fast\SisdikBundle\Entity\Kelas $kelas
     * @return KepulanganSiswa
     */
    public function setKelas(\Fast\SisdikBundle\Entity\Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    
        return $this;
    }

    /**
     * Get kelas
     *
     * @return \Fast\SisdikBundle\Entity\Kelas 
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return KepulanganSiswa
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    
        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getSiswa()
    {
        return $this->siswa;
    }

    /**
     * Set statusKehadiranKepulangan
     *
     * @param \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $statusKehadiranKepulangan
     * @return KepulanganSiswa
     */
    public function setStatusKehadiranKepulangan(\Fast\SisdikBundle\Entity\StatusKehadiranKepulangan $statusKehadiranKepulangan = null)
    {
        $this->statusKehadiranKepulangan = $statusKehadiranKepulangan;
    
        return $this;
    }

    /**
     * Get statusKehadiranKepulangan
     *
     * @return \Fast\SisdikBundle\Entity\StatusKehadiranKepulangan 
     */
    public function getStatusKehadiranKepulangan()
    {
        return $this->statusKehadiranKepulangan;
    }
}