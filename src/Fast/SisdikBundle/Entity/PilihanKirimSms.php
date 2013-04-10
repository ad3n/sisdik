<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * PilihanKirimSms
 *
 * @ORM\Table(name="pilihan_kirim_sms", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="fk_pilihan_kirim_sms_sekolah1_idx", columns={"sekolah_id"})
 * })
 * @ORM\Entity
 */
class PilihanKirimSms
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
     * @var boolean
     *
     * @ORM\Column(name="pendaftaran_tercatat", type="boolean", nullable=true, options={"default"="1"})
     */
    private $pendaftaranTercatat = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pendaftaran_bayar_pertama", type="boolean", nullable=true, options={"default"="1"})
     */
    private $pendaftaranBayarPertama = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pendaftaran_bayar", type="boolean", nullable=true, options={"default"="1"})
     */
    private $pendaftaranBayar = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="pendaftaran_bayar_lunas", type="boolean", nullable=true, options={"default"="1"})
     */
    private $pendaftaranBayarLunas = true;

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
     * Set pendaftaranTercatat
     *
     * @param boolean $pendaftaranTercatat
     * @return PilihanKirimSms
     */
    public function setPendaftaranTercatat($pendaftaranTercatat) {
        $this->pendaftaranTercatat = $pendaftaranTercatat;

        return $this;
    }

    /**
     * Get pendaftaranTercatat
     *
     * @return boolean
     */
    public function getPendaftaranTercatat() {
        return $this->pendaftaranTercatat;
    }

    /**
     * Set pendaftaranBayarPertama
     *
     * @param boolean $pendaftaranBayarPertama
     * @return PilihanKirimSms
     */
    public function setPendaftaranBayarPertama($pendaftaranBayarPertama) {
        $this->pendaftaranBayarPertama = $pendaftaranBayarPertama;

        return $this;
    }

    /**
     * Get pendaftaranBayarPertama
     *
     * @return boolean
     */
    public function getPendaftaranBayarPertama() {
        return $this->pendaftaranBayarPertama;
    }

    /**
     * Set pendaftaranBayar
     *
     * @param boolean $pendaftaranBayar
     * @return PilihanKirimSms
     */
    public function setPendaftaranBayar($pendaftaranBayar) {
        $this->pendaftaranBayar = $pendaftaranBayar;

        return $this;
    }

    /**
     * Get pendaftaranBayar
     *
     * @return boolean
     */
    public function getPendaftaranBayar() {
        return $this->pendaftaranBayar;
    }

    /**
     * Set pendaftaranBayarLunas
     *
     * @param boolean $pendaftaranBayarLunas
     * @return PilihanKirimSms
     */
    public function setPendaftaranBayarLunas($pendaftaranBayarLunas) {
        $this->pendaftaranBayarLunas = $pendaftaranBayarLunas;

        return $this;
    }

    /**
     * Get pendaftaranBayarLunas
     *
     * @return boolean
     */
    public function getPendaftaranBayarLunas() {
        return $this->pendaftaranBayarLunas;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return PilihanKirimSms
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
