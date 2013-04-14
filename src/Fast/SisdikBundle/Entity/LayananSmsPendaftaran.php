<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * LayananSmsPendaftaran
 *
 * @ORM\Table(name="layanan_sms_pendaftaran", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="sms_pendaftaran_UNIQUE", columns={"sekolah_id", "jenis_layanan"})
 * })
 * @ORM\Entity
 */
class LayananSmsPendaftaran
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
     * @ORM\Column(name="jenis_layanan", type="string", length=45, nullable=true)
     */
    private $jenisLayanan;

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
     * @var \Templatesms
     *
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="templatesms_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $templatesms;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set jenisLayanan
     *
     * @param string $jenisLayanan
     * @return LayananSmsPendaftaran
     */
    public function setJenisLayanan($jenisLayanan) {
        $this->jenisLayanan = $jenisLayanan;

        return $this;
    }

    /**
     * Get jenisLayanan
     *
     * @return string
     */
    public function getJenisLayanan() {
        return $this->jenisLayanan;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return LayananSmsPendaftaran
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
     * Set templatesms
     *
     * @param \Fast\SisdikBundle\Entity\Templatesms $templatesms
     * @return LayananSmsPendaftaran
     */
    public function setTemplatesms(\Fast\SisdikBundle\Entity\Templatesms $templatesms = null) {
        $this->templatesms = $templatesms;

        return $this;
    }

    /**
     * Get templatesms
     *
     * @return \Fast\SisdikBundle\Entity\Templatesms
     */
    public function getTemplatesms() {
        return $this->templatesms;
    }
}
