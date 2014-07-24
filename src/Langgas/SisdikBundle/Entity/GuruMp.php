<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="guru_mp")
 * @ORM\Entity
 */
class GuruMp
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
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="guru_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Guru
     */
    private $guru;

    /**
     * @ORM\ManyToOne(targetEntity="MataPelajaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="mata_pelajaran_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var MataPelajaran
     */
    private $mataPelajaran;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Guru $guru
     */
    public function setGuru(Guru $guru = null)
    {
        $this->guru = $guru;
    }

    /**
     * @return Guru
     */
    public function getGuru()
    {
        return $this->guru;
    }

    /**
     * @param MataPelajaran $mataPelajaran
     */
    public function setMataPelajaran(MataPelajaran $mataPelajaran = null)
    {
        $this->mataPelajaran = $mataPelajaran;
    }

    /**
     * @return MataPelajaran
     */
    public function getMataPelajaran()
    {
        return $this->mataPelajaran;
    }
}
