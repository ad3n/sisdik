<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Referensi
 *
 * @ORM\Table(name="referensi")
 * @ORM\Entity
 */
class Referensi
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="ponsel", type="string", length=50, nullable=true)
     */
    private $ponsel;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_identitas", type="string", length=300, nullable=true)
     */
    private $nomorIdentitas;



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
     * Set nama
     *
     * @param string $nama
     * @return Referensi
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    
        return $this;
    }

    /**
     * Get nama
     *
     * @return string 
     */
    public function getNama()
    {
        return $this->nama;
    }

    /**
     * Set ponsel
     *
     * @param string $ponsel
     * @return Referensi
     */
    public function setPonsel($ponsel)
    {
        $this->ponsel = $ponsel;
    
        return $this;
    }

    /**
     * Get ponsel
     *
     * @return string 
     */
    public function getPonsel()
    {
        return $this->ponsel;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return Referensi
     */
    public function setAlamat($alamat)
    {
        $this->alamat = $alamat;
    
        return $this;
    }

    /**
     * Get alamat
     *
     * @return string 
     */
    public function getAlamat()
    {
        return $this->alamat;
    }

    /**
     * Set nomorIdentitas
     *
     * @param string $nomorIdentitas
     * @return Referensi
     */
    public function setNomorIdentitas($nomorIdentitas)
    {
        $this->nomorIdentitas = $nomorIdentitas;
    
        return $this;
    }

    /**
     * Get nomorIdentitas
     *
     * @return string 
     */
    public function getNomorIdentitas()
    {
        return $this->nomorIdentitas;
    }
}