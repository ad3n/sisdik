<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * @ORM\Column(name="nama", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $nama;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $nama
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    }

    /**
     * @return string
     */
    public function getNama()
    {
        return $this->nama;
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
}
