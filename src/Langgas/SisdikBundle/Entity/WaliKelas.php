<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="wali_kelas", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="wali_kelas_unq1", columns={"tahun_akademik_id", "kelas_id"})
 * })
 * @ORM\Entity
 */
class WaliKelas
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="keterangan", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="kirim_ikhtisar_kehadiran", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $kirimIkhtisarKehadiran = false;

    /**
     * @ORM\Column(name="jadwal_kirim_ikhtisar_kehadiran", type="smallint", nullable=false, options={"default"="0"})
     *
     * @var string
     */
    private $jadwalKirimIkhtisarKehadiran = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Kelas")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="kelas_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Kelas
     */
    private $kelas;

    /**
     * @ORM\ManyToOne(targetEntity="TahunAkademik")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_akademik_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var TahunAkademik
     */
    private $tahunAkademik;

    /**
     * @var string
     */
    private $namaUser;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull(message="Wali Kelas (User) tidak boleh kosong")
     *
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="templatesms_ikhtisar_kehadiran_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Templatesms
     */
    private $templatesmsIkhtisarKehadiran;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param boolean $kirimIkhtisarKehadiran
     */
    public function setKirimIkhtisarKehadiran($kirimIkhtisarKehadiran)
    {
        $this->kirimIkhtisarKehadiran = $kirimIkhtisarKehadiran;
    }

    /**
     * @return boolean
     */
    public function isKirimIkhtisarKehadiran()
    {
        return $this->kirimIkhtisarKehadiran;
    }

    /**
     * @param string $jadwalKirimIkhtisarKehadiran
     */
    public function setJadwalKirimIkhtisarKehadiran($jadwalKirimIkhtisarKehadiran)
    {
        $this->jadwalKirimIkhtisarKehadiran = $jadwalKirimIkhtisarKehadiran;
    }

    /**
     * @return integer
     */
    public function getJadwalKirimIkhtisarKehadiran()
    {
        return $this->jadwalKirimIkhtisarKehadiran;
    }

    /**
     * @param Kelas $kelas
     */
    public function setKelas(Kelas $kelas = null)
    {
        $this->kelas = $kelas;
    }

    /**
     * @return Kelas
     */
    public function getKelas()
    {
        return $this->kelas;
    }

    /**
     * @param TahunAkademik $tahunAkademik
     */
    public function setTahunAkademik(TahunAkademik $tahunAkademik = null)
    {
        $this->tahunAkademik = $tahunAkademik;
    }

    /**
     * @return TahunAkademik
     */
    public function getTahunAkademik()
    {
        return $this->tahunAkademik;
    }

    /**
     * @param string $namaUser
     */
    public function setNamaUser($namaUser)
    {
        $this->namaUser = $namaUser;
    }

    /**
     * @return string
     */
    public function getNamaUser()
    {
        return $this->namaUser;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Templatesms $templatesmsIkhtisarKehadiran
     */
    public function setTemplatesmsIkhtisarKehadiran(Templatesms $templatesmsIkhtisarKehadiran = null)
    {
        $this->templatesmsIkhtisarKehadiran = $templatesmsIkhtisarKehadiran;
    }

    /**
     * @return Templatesms
     */
    public function getTemplatesmsIkhtisarKehadiran()
    {
        return $this->templatesmsIkhtisarKehadiran;
    }
}
