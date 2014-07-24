<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="token_sekolah")
 * @ORM\Entity
 */
class TokenSekolah
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
     * @ORM\Column(name="mesin_proxy", type="string", length=255, nullable=false)
     *
     * @var string
     */
    private $mesinProxy;

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
     * @param string $mesinProxy
     */
    public function setMesinProxy($mesinProxy)
    {
        $this->mesinProxy = $mesinProxy;
    }

    /**
     * @return string
     */
    public function getMesinProxy()
    {
        return $this->mesinProxy;
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

    public function generateRandomToken()
    {
        return sha1(uniqid(mt_rand(), true));
    }
}
