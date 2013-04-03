<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PanitiaPendaftaran
 *
 * @ORM\Table(name="panitia_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="tahun_id_UNIQUE", columns={"tahun_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PanitiaPendaftaran
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
     * @ORM\Column(name="panitia", type="text", nullable=true)
     */
    private $panitia;

    /**
     * @var boolean
     *
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"=0})
     */
    private $aktif = 0;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ketua_panitia_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $ketuaPanitia;

    /**
     * @var \Tahun
     *
     * @ORM\ManyToOne(targetEntity="Tahun", inversedBy="panitiaPendaftaran")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahun;

    /**
     * @var \Sekolah
     *
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $sekolah;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set panitia
     *
     * @param string $panitia
     * @return PanitiaPendaftaran
     */
    public function setPanitia($panitia) {
        $this->panitia = $panitia;

        return $this;
    }

    /**
     * Get panitia
     *
     * @return array
     */
    public function getPanitia() {
        return unserialize($this->panitia);
    }

    /**
     * Set aktif
     *
     * @param boolean $aktif
     * @return PanitiaPendaftaran
     */
    public function setAktif($aktif) {
        $this->aktif = $aktif;

        return $this;
    }

    /**
     * Get aktif
     *
     * @return boolean
     */
    public function getAktif() {
        return $this->aktif;
    }

    /**
     * Set tahun
     *
     * @param \Fast\SisdikBundle\Entity\Tahun $tahun
     * @return PanitiaPendaftaran
     */
    public function setTahun(\Fast\SisdikBundle\Entity\Tahun $tahun = null) {
        $this->tahun = $tahun;

        return $this;
    }

    /**
     * Get tahun
     *
     * @return \Fast\SisdikBundle\Entity\Tahun
     */
    public function getTahun() {
        return $this->tahun;
    }

    /**
     * Set ketuaPanitia
     *
     * @param \Fast\SisdikBundle\Entity\User $ketuaPanitia
     * @return PanitiaPendaftaran
     */
    public function setKetuaPanitia(\Fast\SisdikBundle\Entity\User $ketuaPanitia = null) {
        $this->ketuaPanitia = $ketuaPanitia;

        return $this;
    }

    /**
     * Get ketuaPanitia
     *
     * @return \Fast\SisdikBundle\Entity\User
     */
    public function getKetuaPanitia() {
        return $this->ketuaPanitia;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return PanitiaPendaftaran
     */
    public function setSekolah(\Fast\SisdikBundle\Entity\Sekolah $sekolah = null) {
        $this->sekolah = $sekolah;

        return $this;
    }

    /**
     * Get sekolah
     *
     * @return \Fast\SisdikBundle\Entity\Sekolah
     */
    public function getSekolah() {
        return $this->sekolah;
    }

    /**
     *
     * @var ArrayCollection
     */
    private $daftarPersonil;

    public function __construct() {
        $this->daftarPersonil = new ArrayCollection();
    }

    /**
     * Get daftarPersonil
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $daftarPersonil
     */
    public function getDaftarPersonil() {
        if ($this->daftarPersonil instanceof ArrayCollection) {
            return $this->daftarPersonil;
        }

        $this->daftarPersonil = new ArrayCollection();
        if (is_array($this->getPanitia())) {
            foreach ($this->getPanitia() as $panitia) {
                $entity = new Personil();
                $entity->setId($panitia);

                $this->daftarPersonil->add($entity);
            }
        }

        return $this->daftarPersonil;
    }

    /**
     * Set daftarPersonil
     *
     * @param ArrayCollection $daftarPersonil
     */
    public function setDaftarPersonil(ArrayCollection $daftarPersonil) {
        $this->daftarPersonil = $daftarPersonil;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preSave() {
        $values = array();

        foreach ($this->daftarPersonil as $personil) {
            $values[] = $personil->getId();
        }

        $this->panitia = serialize($values);
    }
}
