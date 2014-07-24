<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="panitia_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="sekolah_tahun_id_UNIQUE", columns={"sekolah_id", "tahun_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PanitiaPendaftaran
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
     * @ORM\Column(name="panitia", type="text", nullable=true)
     *
     * @var string
     */
    private $panitia;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $aktif = 0;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="ketua_panitia_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var User
     */
    private $ketuaPanitia;

    /**
     * @ORM\ManyToOne(targetEntity="Tahun", inversedBy="panitiaPendaftaran")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Tahun
     */
    private $tahun;

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
     * @var ArrayCollection
     */
    private $daftarPersonil;

    public function __construct()
    {
        $this->daftarPersonil = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $panitia
     */
    public function setPanitia($panitia)
    {
        $this->panitia = $panitia;
    }

    /**
     * @return array
     */
    public function getPanitia()
    {
        return unserialize($this->panitia);
    }

    /**
     * @param boolean $aktif
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    }

    /**
     * @return boolean
     */
    public function getAktif()
    {
        return $this->aktif;
    }

    /**
     * @param Tahun $tahun
     */
    public function setTahun(Tahun $tahun = null)
    {
        $this->tahun = $tahun;
    }

    /**
     * @return Tahun
     */
    public function getTahun()
    {
        return $this->tahun;
    }

    /**
     * @param User $ketuaPanitia
     */
    public function setKetuaPanitia(User $ketuaPanitia = null)
    {
        $this->ketuaPanitia = $ketuaPanitia;
    }

    /**
     * @return User
     */
    public function getKetuaPanitia()
    {
        return $this->ketuaPanitia;
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
     * @return ArrayCollection $daftarPersonil
     */
    public function getDaftarPersonil()
    {
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
     * @param ArrayCollection $daftarPersonil
     */
    public function setDaftarPersonil(ArrayCollection $daftarPersonil)
    {
        $this->daftarPersonil = $daftarPersonil;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preSave()
    {
        $values = [];

        foreach ($this->daftarPersonil as $personil) {
            $values[] = $personil->getId();
        }

        $this->panitia = serialize($values);
    }
}
