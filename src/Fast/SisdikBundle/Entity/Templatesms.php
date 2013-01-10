<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Templatesms
 *
 * @ORM\Table(name="templatesms")
 * @ORM\Entity
 */
class Templatesms
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
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=50, nullable=true)
     */
    private $nama;

    /**
     * @var string $teks
     *
     * @ORM\Column(name="teks", type="string", length=500, nullable=true)
     */
    private $teks;

    /**
     * @var string $keterangan
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

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
    public function getId() {
        return $this->id;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return Templatesms
     */
    public function setNama($nama) {
        $this->nama = $nama;

        return $this;
    }

    /**
     * Get nama
     *
     * @return string 
     */
    public function getNama() {
        return $this->nama;
    }

    /**
     * Set teks
     *
     * @param string $teks
     * @return Templatesms
     */
    public function setTeks($teks) {
        $this->teks = $teks;

        return $this;
    }

    /**
     * Get teks
     *
     * @return string 
     */
    public function getTeks() {
        return $this->teks;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return Templatesms
     */
    public function setKeterangan($keterangan) {
        $this->keterangan = $keterangan;

        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string 
     */
    public function getKeterangan() {
        return $this->keterangan;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Templatesms
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = null) {
        $this->idsekolah = $idsekolah;

        return $this;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah() {
        return $this->idsekolah;
    }

    public function getOptionLabel() {
        $teks = $this->getTeks();
        return $this->getNama() . " » "
                . (strlen($teks) > 80 ? substr($teks, 0, 25) . '...'
                                . substr($teks, strlen($teks) - 53) : $teks);
    }
}
