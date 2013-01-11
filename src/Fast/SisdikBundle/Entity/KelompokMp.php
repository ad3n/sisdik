<?php

namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KelompokMp
 *
 * @ORM\Table(name="kelompok_mp")
 * @ORM\Entity
 */
class KelompokMp
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
     * @var integer
     *
     * @ORM\Column(name="urutan", type="integer", nullable=true)
     */
    private $urutan;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id")
     * })
     */
    private $sekolah;



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
     * @return KelompokMp
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
     * Set urutan
     *
     * @param integer $urutan
     * @return KelompokMp
     */
    public function setUrutan($urutan)
    {
        $this->urutan = $urutan;
    
        return $this;
    }

    /**
     * Get urutan
     *
     * @return integer 
     */
    public function getUrutan()
    {
        return $this->urutan;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return KelompokMp
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null)
    {
        $this->sekolah = $sekolah;
    
        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getSekolah()
    {
        return $this->sekolah;
    }
}