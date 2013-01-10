<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Jenjang
 *
 * @ORM\Table(name="jenjang")
 * @ORM\Entity
 */
class Jenjang
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
     * @var string $kode
     *
     * @ORM\Column(name="kode", type="string", length=45, nullable=false)
     */
    private $kode;

    /**
     * @var string $nama
     *
     * @ORM\Column(name="nama", type="string", length=50, nullable=true)
     */
    private $nama;

    /**
     * @var integer $urutan
     *
     * @ORM\Column(name="urutan", type="smallint", nullable=true)
     */
    private $urutan;

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
     * Set kode
     *
     * @param string $kode
     * @return Jenjang
     */
    public function setKode($kode) {
        $this->kode = $kode;

        return $this;
    }

    /**
     * Get kode
     *
     * @return string 
     */
    public function getKode() {
        return $this->kode;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return Jenjang
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
     * Set urutan
     *
     * @param integer $urutan
     * @return Jenjang
     */
    public function setUrutan($urutan) {
        $this->urutan = $urutan;

        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer 
     */
    public function getUrutan() {
        return $this->urutan;
    }

    /**
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     * @return Jenjang
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
        return $this->getKode() . ' (' . $this->getNama() . ')';
    }
}
