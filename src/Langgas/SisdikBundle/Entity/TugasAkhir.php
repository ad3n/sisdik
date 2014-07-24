<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tugas_akhir")
 * @ORM\Entity
 */
class TugasAkhir
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
     * @ORM\Column(name="judul", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $judul;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $judul
     */
    public function setJudul($judul)
    {
        $this->judul = $judul;
    }

    /**
     * @return string
     */
    public function getJudul()
    {
        return $this->judul;
    }

    /**
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }
}
