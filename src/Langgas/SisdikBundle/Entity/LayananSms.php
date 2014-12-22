<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="layanan_sms", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="layanan_sms_UNIQUE", columns={"sekolah_id", "jenis_layanan"})
 * })
 * @ORM\Entity
 */
class LayananSms
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
     * @ORM\Column(name="jenis_layanan", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $jenisLayanan;

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
     * @ORM\ManyToOne(targetEntity="Templatesms")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="templatesms_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Templatesms
     */
    private $templatesms;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $jenisLayanan
     */
    public function setJenisLayanan($jenisLayanan)
    {
        $this->jenisLayanan = $jenisLayanan;
    }

    /**
     * @return string
     */
    public function getJenisLayanan()
    {
        return $this->jenisLayanan;
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
     * @param Templatesms $templatesms
     */
    public function setTemplatesms(Templatesms $templatesms = null)
    {
        $this->templatesms = $templatesms;
    }

    /**
     * @return Templatesms
     */
    public function getTemplatesms()
    {
        return $this->templatesms;
    }
}
