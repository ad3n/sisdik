<?php

namespace Langgas\SisdikBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;

/**
 * @ORM\Table(name="fos_user")
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(min=3, max=255, minMessage="The name is too short.", maxMessage="The name is too long.", groups={"Registration", "Profile"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Guru", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="guru_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Guru
     */
    private $guru;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @ORM\ManyToOne(targetEntity="Staf", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="staf_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Staf
     */
    private $staf;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }

    /**
     * @param Staf $staf
     */
    public function setStaf(Staf $staf = null)
    {
        $this->staf = $staf;
    }

    /**
     * @return Staf
     */
    public function getStaf()
    {
        return $this->staf;
    }
}
