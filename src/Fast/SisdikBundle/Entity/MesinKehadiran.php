<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\SerializedName;

/**
 * @ORM\Table(name="mesin_kehadiran")
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class MesinKehadiran
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
     * @ORM\Column(name="alamat_ip", type="string", length=45, nullable=false)
     * @Expose
     * @SerializedName("alamat_ip")
     *
     * @var string
     */
    private $alamatIp;

    /**
     * @ORM\Column(name="commkey", type="string", length=45, nullable=false)
     * @Expose
     *
     * @var string
     */
    private $commkey;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"=1})
     * @Expose
     * @SerializedName("aktif")
     *
     * @var boolean
     */
    private $aktif = true;

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
     * @param string $alamatIp
     */
    public function setAlamatIp($alamatIp)
    {
        $this->alamatIp = $alamatIp;
    }

    /**
     * @return string
     */
    public function getAlamatIp()
    {
        return $this->alamatIp;
    }

    /**
     * @param string $commkey
     */
    public function setCommkey($commkey)
    {
        $this->commkey = $commkey;
    }

    /**
     * @return string
     */
    public function getCommkey()
    {
        return $this->commkey;
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
}
