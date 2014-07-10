<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="kehadiran_guru")
 * @ORM\Entity
 */
class KehadiranGuru
{
    /**
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggal;

    /**
     * @ORM\Column(name="hadir", type="boolean", nullable=true)
     *
     * @var boolean
     */
    private $hadir;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $tanggal
     */
    public function setTanggal($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    /**
     * @return \DateTime
     */
    public function getTanggal()
    {
        return $this->tanggal;
    }

    /**
     * @param boolean $hadir
     */
    public function setHadir($hadir)
    {
        $this->hadir = $hadir;
    }

    /**
     * @return boolean
     */
    public function getHadir()
    {
        return $this->hadir;
    }
}
