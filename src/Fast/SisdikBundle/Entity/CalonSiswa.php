<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalonSiswa
 *
 * @ORM\Table(name="calon_siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="nomor_pendaftaran_UNIQUE", columns={"nomor_pendaftaran"}),
 *     @ORM\UniqueConstraint(name="calon_siswa_UNIQUE1", columns={"tahunmasuk_id", "nomor_urut_pendaftaran"})
 * })
 * @ORM\Entity
 */
class CalonSiswa
{
    const WEBCAMPHOTO_DIR = 'uploads/applicants/webcam-photos/';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="nomor_urut_pendaftaran", type="smallint", nullable=true, options={"unsigned"=true})
     */
    private $nomorUrutPendaftaran;

    /**
     * @var string
     *
     * @ORM\Column(name="nomor_pendaftaran", type="string", length=45, nullable=true)
     */
    private $nomorPendaftaran;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     * @Assert\NotBlank
     */
    private $namaLengkap;

    /**
     * @var string
     *
     * @ORM\Column(name="jenis_kelamin", type="string", length=100, nullable=true)
     */
    private $jenisKelamin;

    /**
     * @var string
     *
     * @ORM\Column(name="foto_pendaftaran", type="string", length=100, nullable=true)
     */
    private $fotoPendaftaran;

    /**
     * @var string
     *
     * @ORM\Column(name="foto", type="string", length=100, nullable=true)
     */
    private $foto;

    /**
     * @var string
     *
     * @ORM\Column(name="agama", type="string", length=100, nullable=true)
     */
    private $agama;

    /**
     * @var string
     *
     * @ORM\Column(name="tempat_lahir", type="string", length=400, nullable=true)
     */
    private $tempatLahir;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tanggal_lahir", type="date", nullable=true)
     */
    private $tanggalLahir;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_panggilan", type="string", length=100, nullable=true)
     */
    private $namaPanggilan;

    /**
     * @var string
     *
     * @ORM\Column(name="kewarganegaraan", type="string", length=200, nullable=true)
     */
    private $kewarganegaraan;

    /**
     * @var integer
     *
     * @ORM\Column(name="anak_ke", type="smallint", nullable=true)
     */
    private $anakKe;

    /**
     * @var integer
     *
     * @ORM\Column(name="jumlah_saudarakandung", type="smallint", nullable=true)
     */
    private $jumlahSaudarakandung;

    /**
     * @var integer
     *
     * @ORM\Column(name="jumlah_saudaratiri", type="smallint", nullable=true)
     */
    private $jumlahSaudaratiri;

    /**
     * @var string
     *
     * @ORM\Column(name="status_orphan", type="string", length=100, nullable=true)
     */
    private $statusOrphan;

    /**
     * @var string
     *
     * @ORM\Column(name="bahasa_seharihari", type="string", length=200, nullable=true)
     */
    private $bahasaSeharihari;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string
     *
     * @ORM\Column(name="kodepos", type="string", length=30, nullable=true)
     */
    private $kodepos;

    /**
     * @var string
     *
     * @ORM\Column(name="telepon", type="string", length=100, nullable=true)
     */
    private $telepon;

    /**
     * @var string
     *
     * @ORM\Column(name="ponsel_siswa", type="string", length=100, nullable=true)
     */
    private $ponselSiswa;

    /**
     * @var string
     *
     * @ORM\Column(name="ponsel_orangtuawali", type="string", length=100, nullable=true)
     * @Assert\NotBlank
     */
    private $ponselOrangtuawali;

    /**
     * @var string
     *
     * @ORM\Column(name="sekolah_tinggaldi", type="string", length=400, nullable=true)
     */
    private $sekolahTinggaldi;

    /**
     * @var string
     *
     * @ORM\Column(name="jarak_tempat", type="string", length=300, nullable=true)
     */
    private $jarakTempat;

    /**
     * @var string
     *
     * @ORM\Column(name="cara_kesekolah", type="string", length=300, nullable=true)
     */
    private $caraKesekolah;

    /**
     * @var integer
     *
     * @ORM\Column(name="beratbadan", type="smallint", nullable=true)
     */
    private $beratbadan;

    /**
     * @var integer
     *
     * @ORM\Column(name="tinggibadan", type="smallint", nullable=true)
     */
    private $tinggibadan;

    /**
     * @var string
     *
     * @ORM\Column(name="golongandarah", type="string", length=50, nullable=true)
     */
    private $golongandarah;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     */
    private $waktuSimpan;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     */
    private $waktuUbah;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="dibuat_oleh_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $dibuatOleh;

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
     * @var \Tahunmasuk
     *
     * @ORM\ManyToOne(targetEntity="Tahunmasuk")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tahunmasuk_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $tahunmasuk;

    /**
     * @var \Gelombang
     *
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $gelombang;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="diubah_oleh_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $diubahOleh;

    /**
     * @var \Referensi
     *
     * @ORM\ManyToOne(targetEntity="Referensi")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="referensi_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    private $referensi;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nomorUrutPendaftaran
     *
     * @param integer $nomorPendaftaran
     * @return CalonSiswa
     */
    public function setNomorUrutPendaftaran($nomorUrutPendaftaran) {
        $this->nomorUrutPendaftaran = $nomorUrutPendaftaran;

        return $this;
    }

    /**
     * Get nomorUrutPendaftaran
     *
     * @return integer
     */
    public function getNomorUrutPendaftaran() {
        return $this->nomorUrutPendaftaran;
    }

    /**
     * Set nomorPendaftaran
     *
     * @param string $nomorPendaftaran
     * @return CalonSiswa
     */
    public function setNomorPendaftaran($nomorPendaftaran) {
        $this->nomorPendaftaran = $nomorPendaftaran;

        return $this;
    }

    /**
     * Get nomorPendaftaran
     *
     * @return string
     */
    public function getNomorPendaftaran() {
        return $this->nomorPendaftaran;
    }

    /**
     * Set namaLengkap
     *
     * @param string $namaLengkap
     * @return CalonSiswa
     */
    public function setNamaLengkap($namaLengkap) {
        $this->namaLengkap = $namaLengkap;

        return $this;
    }

    /**
     * Get namaLengkap
     *
     * @return string
     */
    public function getNamaLengkap() {
        return $this->namaLengkap;
    }

    /**
     * Set jenisKelamin
     *
     * @param string $jenisKelamin
     * @return CalonSiswa
     */
    public function setJenisKelamin($jenisKelamin) {
        $this->jenisKelamin = $jenisKelamin;

        return $this;
    }

    /**
     * Get jenisKelamin
     *
     * @return string
     */
    public function getJenisKelamin() {
        return $this->jenisKelamin;
    }

    /**
     * Set fotoPendaftaran
     *
     * @param string $fotoPendaftaran
     * @return CalonSiswa
     */
    public function setFotoPendaftaran($fotoPendaftaran) {
        $this->fotoPendaftaran = $fotoPendaftaran;

        return $this;
    }

    /**
     * Get fotoPendaftaran
     *
     * @return string
     */
    public function getFotoPendaftaran() {
        return $this->fotoPendaftaran;
    }

    /**
     * Set foto
     *
     * @param string $foto
     * @return CalonSiswa
     */
    public function setFoto($foto) {
        $this->foto = $foto;

        return $this;
    }

    /**
     * Get foto
     *
     * @return string
     */
    public function getFoto() {
        return $this->foto;
    }

    /**
     * Set agama
     *
     * @param string $agama
     * @return CalonSiswa
     */
    public function setAgama($agama) {
        $this->agama = $agama;

        return $this;
    }

    /**
     * Get agama
     *
     * @return string
     */
    public function getAgama() {
        return $this->agama;
    }

    /**
     * Set tempatLahir
     *
     * @param string $tempatLahir
     * @return CalonSiswa
     */
    public function setTempatLahir($tempatLahir) {
        $this->tempatLahir = $tempatLahir;

        return $this;
    }

    /**
     * Get tempatLahir
     *
     * @return string
     */
    public function getTempatLahir() {
        return $this->tempatLahir;
    }

    /**
     * Set tanggalLahir
     *
     * @param \DateTime $tanggalLahir
     * @return CalonSiswa
     */
    public function setTanggalLahir($tanggalLahir) {
        $this->tanggalLahir = $tanggalLahir;

        return $this;
    }

    /**
     * Get tanggalLahir
     *
     * @return \DateTime
     */
    public function getTanggalLahir() {
        return $this->tanggalLahir;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return CalonSiswa
     */
    public function setEmail($email) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Set namaPanggilan
     *
     * @param string $namaPanggilan
     * @return CalonSiswa
     */
    public function setNamaPanggilan($namaPanggilan) {
        $this->namaPanggilan = $namaPanggilan;

        return $this;
    }

    /**
     * Get namaPanggilan
     *
     * @return string
     */
    public function getNamaPanggilan() {
        return $this->namaPanggilan;
    }

    /**
     * Set kewarganegaraan
     *
     * @param string $kewarganegaraan
     * @return CalonSiswa
     */
    public function setKewarganegaraan($kewarganegaraan) {
        $this->kewarganegaraan = $kewarganegaraan;

        return $this;
    }

    /**
     * Get kewarganegaraan
     *
     * @return string
     */
    public function getKewarganegaraan() {
        return $this->kewarganegaraan;
    }

    /**
     * Set anakKe
     *
     * @param integer $anakKe
     * @return CalonSiswa
     */
    public function setAnakKe($anakKe) {
        $this->anakKe = $anakKe;

        return $this;
    }

    /**
     * Get anakKe
     *
     * @return integer
     */
    public function getAnakKe() {
        return $this->anakKe;
    }

    /**
     * Set jumlahSaudarakandung
     *
     * @param integer $jumlahSaudarakandung
     * @return CalonSiswa
     */
    public function setJumlahSaudarakandung($jumlahSaudarakandung) {
        $this->jumlahSaudarakandung = $jumlahSaudarakandung;

        return $this;
    }

    /**
     * Get jumlahSaudarakandung
     *
     * @return integer
     */
    public function getJumlahSaudarakandung() {
        return $this->jumlahSaudarakandung;
    }

    /**
     * Set jumlahSaudaratiri
     *
     * @param integer $jumlahSaudaratiri
     * @return CalonSiswa
     */
    public function setJumlahSaudaratiri($jumlahSaudaratiri) {
        $this->jumlahSaudaratiri = $jumlahSaudaratiri;

        return $this;
    }

    /**
     * Get jumlahSaudaratiri
     *
     * @return integer
     */
    public function getJumlahSaudaratiri() {
        return $this->jumlahSaudaratiri;
    }

    /**
     * Set statusOrphan
     *
     * @param string $statusOrphan
     * @return CalonSiswa
     */
    public function setStatusOrphan($statusOrphan) {
        $this->statusOrphan = $statusOrphan;

        return $this;
    }

    /**
     * Get statusOrphan
     *
     * @return string
     */
    public function getStatusOrphan() {
        return $this->statusOrphan;
    }

    /**
     * Set bahasaSeharihari
     *
     * @param string $bahasaSeharihari
     * @return CalonSiswa
     */
    public function setBahasaSeharihari($bahasaSeharihari) {
        $this->bahasaSeharihari = $bahasaSeharihari;

        return $this;
    }

    /**
     * Get bahasaSeharihari
     *
     * @return string
     */
    public function getBahasaSeharihari() {
        return $this->bahasaSeharihari;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return CalonSiswa
     */
    public function setAlamat($alamat) {
        $this->alamat = $alamat;

        return $this;
    }

    /**
     * Get alamat
     *
     * @return string
     */
    public function getAlamat() {
        return $this->alamat;
    }

    /**
     * Set kodepos
     *
     * @param string $kodepos
     * @return CalonSiswa
     */
    public function setKodepos($kodepos) {
        $this->kodepos = $kodepos;

        return $this;
    }

    /**
     * Get kodepos
     *
     * @return string
     */
    public function getKodepos() {
        return $this->kodepos;
    }

    /**
     * Set telepon
     *
     * @param string $telepon
     * @return CalonSiswa
     */
    public function setTelepon($telepon) {
        $this->telepon = $telepon;

        return $this;
    }

    /**
     * Get telepon
     *
     * @return string
     */
    public function getTelepon() {
        return $this->telepon;
    }

    /**
     * Set ponselSiswa
     *
     * @param string $ponselSiswa
     * @return CalonSiswa
     */
    public function setPonselSiswa($ponselSiswa) {
        $this->ponselSiswa = $ponselSiswa;

        return $this;
    }

    /**
     * Get ponselSiswa
     *
     * @return string
     */
    public function getPonselSiswa() {
        return $this->ponselSiswa;
    }

    /**
     * Set ponselOrangtuawali
     *
     * @param string $ponselOrangtuawali
     * @return CalonSiswa
     */
    public function setPonselOrangtuawali($ponselOrangtuawali) {
        $this->ponselOrangtuawali = $ponselOrangtuawali;

        return $this;
    }

    /**
     * Get ponselOrangtuawali
     *
     * @return string
     */
    public function getPonselOrangtuawali() {
        return $this->ponselOrangtuawali;
    }

    /**
     * Set sekolahTinggaldi
     *
     * @param string $sekolahTinggaldi
     * @return CalonSiswa
     */
    public function setSekolahTinggaldi($sekolahTinggaldi) {
        $this->sekolahTinggaldi = $sekolahTinggaldi;

        return $this;
    }

    /**
     * Get sekolahTinggaldi
     *
     * @return string
     */
    public function getSekolahTinggaldi() {
        return $this->sekolahTinggaldi;
    }

    /**
     * Set jarakTempat
     *
     * @param string $jarakTempat
     * @return CalonSiswa
     */
    public function setJarakTempat($jarakTempat) {
        $this->jarakTempat = $jarakTempat;

        return $this;
    }

    /**
     * Get jarakTempat
     *
     * @return string
     */
    public function getJarakTempat() {
        return $this->jarakTempat;
    }

    /**
     * Set caraKesekolah
     *
     * @param string $caraKesekolah
     * @return CalonSiswa
     */
    public function setCaraKesekolah($caraKesekolah) {
        $this->caraKesekolah = $caraKesekolah;

        return $this;
    }

    /**
     * Get caraKesekolah
     *
     * @return string
     */
    public function getCaraKesekolah() {
        return $this->caraKesekolah;
    }

    /**
     * Set beratbadan
     *
     * @param integer $beratbadan
     * @return CalonSiswa
     */
    public function setBeratbadan($beratbadan) {
        $this->beratbadan = $beratbadan;

        return $this;
    }

    /**
     * Get beratbadan
     *
     * @return integer
     */
    public function getBeratbadan() {
        return $this->beratbadan;
    }

    /**
     * Set tinggibadan
     *
     * @param integer $tinggibadan
     * @return CalonSiswa
     */
    public function setTinggibadan($tinggibadan) {
        $this->tinggibadan = $tinggibadan;

        return $this;
    }

    /**
     * Get tinggibadan
     *
     * @return integer
     */
    public function getTinggibadan() {
        return $this->tinggibadan;
    }

    /**
     * Set golongandarah
     *
     * @param string $golongandarah
     * @return CalonSiswa
     */
    public function setGolongandarah($golongandarah) {
        $this->golongandarah = $golongandarah;

        return $this;
    }

    /**
     * Get golongandarah
     *
     * @return string
     */
    public function getGolongandarah() {
        return $this->golongandarah;
    }

    /**
     * Set waktuSimpan
     *
     * @param \DateTime $waktuSimpan
     * @return CalonSiswa
     */
    public function setWaktuSimpan($waktuSimpan) {
        $this->waktuSimpan = $waktuSimpan;

        return $this;
    }

    /**
     * Get waktuSimpan
     *
     * @return \DateTime
     */
    public function getWaktuSimpan() {
        return $this->waktuSimpan;
    }

    /**
     * Set waktuUbah
     *
     * @param \DateTime $waktuUbah
     * @return CalonSiswa
     */
    public function setWaktuUbah($waktuUbah) {
        $this->waktuUbah = $waktuUbah;

        return $this;
    }

    /**
     * Get waktuUbah
     *
     * @return \DateTime
     */
    public function getWaktuUbah() {
        return $this->waktuUbah;
    }

    /**
     * Set dibuatOleh
     *
     * @param \Fast\SisdikBundle\Entity\User $dibuatOleh
     * @return CalonSiswa
     */
    public function setDibuatOleh(\Fast\SisdikBundle\Entity\User $dibuatOleh = null) {
        $this->dibuatOleh = $dibuatOleh;

        return $this;
    }

    /**
     * Get dibuatOleh
     *
     * @return \Fast\SisdikBundle\Entity\User
     */
    public function getDibuatOleh() {
        return $this->dibuatOleh;
    }

    /**
     * Set sekolah
     *
     * @param \Fast\SisdikBundle\Entity\Sekolah $sekolah
     * @return CalonSiswa
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
     * Set tahunmasuk
     *
     * @param \Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk
     * @return CalonSiswa
     */
    public function setTahunmasuk(\Fast\SisdikBundle\Entity\Tahunmasuk $tahunmasuk = null) {
        $this->tahunmasuk = $tahunmasuk;

        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return \Fast\SisdikBundle\Entity\Tahunmasuk
     */
    public function getTahunmasuk() {
        return $this->tahunmasuk;
    }

    /**
     * Set gelombang
     *
     * @param \Fast\SisdikBundle\Entity\Gelombang $gelombang
     * @return CalonSiswa
     */
    public function setGelombang(\Fast\SisdikBundle\Entity\Gelombang $gelombang = null) {
        $this->gelombang = $gelombang;

        return $this;
    }

    /**
     * Get gelombang
     *
     * @return \Fast\SisdikBundle\Entity\Gelombang
     */
    public function getGelombang() {
        return $this->gelombang;
    }

    /**
     * Set diubahOleh
     *
     * @param \Fast\SisdikBundle\Entity\User $diubahOleh
     * @return CalonSiswa
     */
    public function setDiubahOleh(\Fast\SisdikBundle\Entity\User $diubahOleh = null) {
        $this->diubahOleh = $diubahOleh;

        return $this;
    }

    /**
     * Get diubahOleh
     *
     * @return \Fast\SisdikBundle\Entity\User
     */
    public function getDiubahOleh() {
        return $this->diubahOleh;
    }

    /**
     * Set referensi
     *
     * @param \Fast\SisdikBundle\Entity\Referensi $referensi
     * @return CalonSiswa
     */
    public function setReferensi(\Fast\SisdikBundle\Entity\Referensi $referensi = null) {
        $this->referensi = $referensi;

        return $this;
    }

    /**
     * Get referensi
     *
     * @return \Fast\SisdikBundle\Entity\Referensi
     */
    public function getReferensi() {
        return $this->referensi;
    }

    public function getWebcamPhotoDir() {
        return self::WEBCAMPHOTO_DIR;
    }
}
