<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="templatesms")
 * @ORM\Entity
 */
class Templatesms
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
     * @ORM\Column(name="nama", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $teks;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

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
     * @param string $teks
     */
    public function setTeks($teks)
    {
        $this->teks = $teks;
    }

    /**
     * @return string
     */
    public function getTeks()
    {
        return $this->teks;
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
     * @param Sekolah $sekolah
     */
    public function setSekolah(Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    }

    /**
     * @return Sekolah
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }

    /**
     * @return string
     */
    public function getOptionLabel()
    {
        $teks = $this->getTeks();

        return
            $this->getNama()
            . " » "
            . (strlen($teks) > 80 ? substr($teks, 0, 25) . '...' . substr($teks, strlen($teks) - 53) : $teks)
        ;
    }
}
