<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="referensi")
 * @ORM\Entity
 */
class Referensi
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
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(min=3)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="ponsel", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $ponsel;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="nomor_identitas", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $nomorIdentitas;

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
     * @ORM\OneToMany(targetEntity="Siswa", mappedBy="referensi")
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
     * @param string $ponsel
     */
    public function setPonsel($ponsel)
    {
        $this->ponsel = $ponsel;
    }

    /**
     * @return string
     */
    public function getPonsel()
    {
        return $this->ponsel;
    }

    /**
     * @param string $alamat
     */
    public function setAlamat($alamat)
    {
        $this->alamat = $alamat;
    }

    /**
     * @return string
     */
    public function getAlamat()
    {
        return $this->alamat;
    }

    /**
     * @param string $nomorIdentitas
     */
    public function setNomorIdentitas($nomorIdentitas)
    {
        $this->nomorIdentitas = $nomorIdentitas;
    }

    /**
     * @return string
     */
    public function getNomorIdentitas()
    {
        return $this->nomorIdentitas;
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
}
