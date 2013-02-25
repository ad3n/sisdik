<?php

namespace Fast\SisdikBundle\Entity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fast\SisdikBundle\Entity\User
 *
 * @ORM\Table(name="fos_user")
 * @ORM\Entity
 */
class User extends BaseUser
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(min=3, max=255, minMessage="The name is too short.", maxMessage="The name is too long.", groups={"Registration", "Profile"})
     */
    private $name;

    /**
     * @var \Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="guru_id", referencedColumnName="id")
     * })
     */
    private $guru;

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
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id")
     * })
     */
    private $siswa;

    /**
     * @var \Staf
     *
     * @ORM\ManyToOne(targetEntity="Staf")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="staf_id", referencedColumnName="id")
     * })
     */
    private $staf;

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
     * Set name
     *
     * @param string $name
     * @return FosUser
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set guru
     *
     * @param \Fast\SisdikBundle\Entity\Guru $guru
     * @return FosUser
     */
    public function setGuru(\Fast\SisdikBundle\Entity\Guru $guru = null)
    {
        $this->guru = $guru;
    
        return $this;
    }

    /**
     * Get guru
     *
     * @return \Fast\SisdikBundle\Entity\Guru 
     */
    public function getGuru()
    {
        return $this->guru;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return FosUser
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

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return FosUser
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    
        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa 
     */
    public function getSiswa()
    {
        return $this->siswa;
    }

    /**
     * Set staf
     *
     * @param \Fast\SisdikBundle\Entity\Staf $staf
     * @return FosUser
     */
    public function setStaf(\Fast\SisdikBundle\Entity\Staf $staf = null)
    {
        $this->staf = $staf;
    
        return $this;
    }

    /**
     * Get staf
     *
     * @return \Fast\SisdikBundle\Entity\Staf 
     */
    public function getStaf()
    {
        return $this->staf;
    }
}