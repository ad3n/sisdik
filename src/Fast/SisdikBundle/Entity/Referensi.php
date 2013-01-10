<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Fast\SisdikBundle\Entity\Referensi
 *
 * @ORM\Table(name="referensi")
 * @ORM\Entity
 */
class Referensi
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
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     */
    private $nama;

    /**
     * @var string $ponsel
     *
     * @ORM\Column(name="ponsel", type="string", length=50, nullable=true)
     */
    private $ponsel;

    /**
     * @var string $alamat
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;



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
     * Set email
     *
     * @param string $email
     * @return Referensi
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
}