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
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(min=3, max=255, minMessage="The name is too short.", maxMessage="The name is too long.", groups={"Registration", "Profile"})
     */
    protected $name;
    
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
     * @var Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idsiswa", referencedColumnName="id")
     * })
     */
    private $idsiswa;
    
    /**
     * @var Staf
     *
     * @ORM\ManyToOne(targetEntity="Staf", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idstaf", referencedColumnName="id")
     * })
     */
    private $idstaf;
    
    /**
     * @var Guru
     *
     * @ORM\ManyToOne(targetEntity="Guru", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="idguru", referencedColumnName="id")
     * })
     */
    private $idguru;
    
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        $this->name = $name;
    }

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
     * Set idsekolah
     *
     * @param Fast\SisdikBundle\Entity\Sekolah $idsekolah
     */
    public function setIdsekolah(\Fast\SisdikBundle\Entity\Sekolah $idsekolah = NULL)
    {
        $this->idsekolah = $idsekolah;
    }

    /**
     * Get idsekolah
     *
     * @return Fast\SisdikBundle\Entity\Sekolah 
     */
    public function getIdsekolah()
    {
        return $this->idsekolah;
    }

    /**
     * Set idsiswa
     * 
     * @param Fast\SisdikBundle\Entity\Siswa $idsiswa
     */
    public function setIdsiswa(\Fast\SisdikBundle\Entity\Siswa $idsiswa = NULL)
    {
        $this->idsiswa = $idsiswa;
    }

    /**
     * Get idsiswa
     *
     * @return Fast\SisdikBundle\Entity\Siswa 
     */
    public function getIdsiswa()
    {
        return $this->idsiswa;
    }

    /**
     * Set idstaf
     *
     * @param Fast\SisdikBundle\Entity\Staf $idstaf
     */
    public function setIdstaf(\Fast\SisdikBundle\Entity\Staf $idstaf = NULL)
    {
        $this->idstaf = $idstaf;
    }

    /**
     * Get idstaf
     *
     * @return Fast\SisdikBundle\Entity\Staf 
     */
    public function getIdstaf()
    {
        return $this->idstaf;
    }

    /**
     * Set idguru
     *
     * @param Fast\SisdikBundle\Entity\Guru $idguru
     */
    public function setIdguru(\Fast\SisdikBundle\Entity\Guru $idguru = NULL)
    {
        $this->idguru = $idguru;
    }

    /**
     * Get idguru
     *
     * @return Fast\SisdikBundle\Entity\Guru 
     */
    public function getIdguru()
    {
        return $this->idguru;
    }
}