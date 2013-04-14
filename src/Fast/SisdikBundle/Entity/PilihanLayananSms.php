<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * PilihanLayananSms
 *
 * @ORM\Table(name="pilihan_layanan_sms", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="pilihan_layanan_sms_UNIQUE", columns={"sekolah_id", "jenis_layanan"})
 * })
 * @ORM\Entity
 */
class PilihanLayananSms
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
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=true, options={"default"="1"})
     */
    private $status = true;

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
     * Daftar layanan yang bisa ada di database
     *
     * @return array
     */
    public static function getDaftarLayanan() {
        return array(
                '1-pendaftaran-tercatat' => 'Pendaftar tercatat di sistem',
                '2-pendaftaran-bayar-pertama' => 'Pendaftar pertama kali membayar biaya pendaftaran',
                '3-pendaftaran-bayar' => 'Pendaftar membayar biaya pendaftaran',
                '4-pendaftaran-bayar-lunas' => 'Calon siswa atau siswa lunas membayar biaya pendaftaran',
        );
    }

    /**
     * Set jenisLayanan
     *
     * @param string $jenisLayanan
     * @return PilihanLayananSms
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
     * Set status
     *
     * @param boolean $status
     * @return PilihanLayananSms
     */
    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return PilihanLayananSms
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
}
