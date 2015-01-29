<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Langgas\SisdikBundle\Util\FileSizeFormatter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="nomor_pendaftaran_UNIQUE", columns={"tahun_id", "nomor_pendaftaran"}),
 *     @ORM\UniqueConstraint(name="nomor_urut_pendaftaran_UNIQUE", columns={"tahun_id", "nomor_urut_pendaftaran"}),
 *     @ORM\UniqueConstraint(name="nomor_urut_persekolah_UNIQUE", columns={"sekolah_id", "nomor_urut_persekolah"}),
 *     @ORM\UniqueConstraint(name="nomor_induk_sistem_UNIQUE", columns={"nomor_induk_sistem"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Siswa
{
    const WEBCAMPHOTO_DIR = 'uploads/students/webcam-photos/';
    const PHOTO_DIR = 'uploads/students/photos/';
    const THUMBNAIL_PREFIX = 'th1-';
    const MEMORY_LIMIT = '256M';
    const PHOTO_THUMB_WIDTH = 80;
    const PHOTO_THUMB_HEIGHT = 150;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nomor_urut_pendaftaran", type="smallint", nullable=true, options={"unsigned"=true})
     *
     * @var integer
     */
    private $nomorUrutPendaftaran;

    /**
     * @ORM\Column(name="nomor_pendaftaran", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $nomorPendaftaran;

    /**
     * @ORM\Column(name="calon_siswa", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $calonSiswa = true;

    /**
     * @ORM\Column(name="nomor_urut_persekolah", type="integer", nullable=true, options={"unsigned"=true})
     *
     * @var integer
     */
    private $nomorUrutPersekolah;

    /**
     * @ORM\Column(name="nomor_induk_sistem", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $nomorIndukSistem;

    /**
     * @ORM\Column(name="nomor_induk", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $nomorInduk;

    /**
     * @ORM\Column(name="nama_lengkap", type="string", length=300, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $namaLengkap;

    /**
     * @ORM\Column(name="jenis_kelamin", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $jenisKelamin;

    /**
     * @ORM\Column(name="foto_pendaftaran", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $fotoPendaftaran;

    /**
     * @Assert\File(maxSize="5M")
     *
     * @var UploadedFile
     */
    private $file;

    /**
     * @ORM\Column(name="foto", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $foto;

    /**
     * @ORM\Column(name="foto_disk", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $fotoDisk;

    /**
     * @var string
     */
    private $fotoDiskSebelumnya;

    /**
     * @ORM\Column(name="agama", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $agama;

    /**
     * @ORM\Column(name="tempat_lahir", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $tempatLahir;

    /**
     * @ORM\Column(name="tanggal_lahir", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $tanggalLahir;

    /**
     * @ORM\Column(name="email", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="nama_panggilan", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $namaPanggilan;

    /**
     * @ORM\Column(name="kewarganegaraan", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $kewarganegaraan;

    /**
     * @ORM\Column(name="anak_ke", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $anakKe;

    /**
     * @ORM\Column(name="jumlah_saudarakandung", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $jumlahSaudarakandung;

    /**
     * @ORM\Column(name="jumlah_saudaratiri", type="smallint", nullable=true)
     *
     * @var integer
     */
    private $jumlahSaudaratiri;

    /**
     * @ORM\Column(name="status_orphan", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $statusOrphan;

    /**
     * @ORM\Column(name="bahasa_seharihari", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $bahasaSeharihari;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="kodepos", type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $kodepos;

    /**
     * @ORM\Column(name="telepon", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $telepon;

    /**
     * @ORM\Column(name="ponsel_siswa", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $ponselSiswa;

    /**
     * @ORM\Column(name="sekolah_tinggaldi", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $sekolahTinggaldi;

    /**
     * @var string
     *
     * @ORM\Column(name="jarak_tempat", type="string", length=300, nullable=true)
     */
    private $jarakTempat;

    /**
     * @ORM\Column(name="cara_kesekolah", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $caraKesekolah;

    /**
     * @ORM\Column(name="beratbadan", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $beratbadan;

    /**
     * @ORM\Column(name="tinggibadan", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $tinggibadan;

    /**
     * @ORM\Column(name="golongandarah", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $golongandarah;

    /**
     * @ORM\Column(name="lunas_biaya_pendaftaran", type="boolean", nullable=false, options={"default" = 0})
     *
     * @var boolean
     */
    private $lunasBiayaPendaftaran = false;

    /**
     * @ORM\Column(name="sisa_biaya_pendaftaran", type="bigint", nullable=false, options={"default" = -999})
     *
     * @var integer
     */
    private $sisaBiayaPendaftaran = -999;

    /**
     * @ORM\Column(name="lunas_biaya_sekali", type="boolean", nullable=false, options={"default" = 0})
     *
     * @var boolean
     */
    private $lunasBiayaSekali = false;

    /**
     * @ORM\Column(name="sisa_biaya_sekali", type="bigint", nullable=false, options={"default" = -999})
     *
     * @var integer
     */
    private $sisaBiayaSekali = -999;

    /**
     * @ORM\Column(name="keterangan", type="text", nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="waktu_simpan", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     *
     * @var \DateTime
     */
    private $waktuSimpan;

    /**
     * @ORM\Column(name="waktu_ubah", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     *
     * @var \DateTime
     */
    private $waktuUbah;

    /**
     * @ORM\ManyToOne(targetEntity="Gelombang")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="gelombang_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Gelombang
     */
    private $gelombang;

    /**
     * @ORM\ManyToOne(targetEntity="Tahun")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="tahun_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull
     *
     * @var Tahun
     */
    private $tahun;

    /**
     * @ORM\ManyToOne(targetEntity="Sekolah")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull
     *
     * @var Sekolah
     */
    private $sekolah;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="dibuat_oleh_id", referencedColumnName="id", nullable=false)
     * })
     * @Assert\NotNull
     *
     * @var User
     */
    private $dibuatOleh;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="diubah_oleh_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var User
     */
    private $diubahOleh;

    /**
     * @var boolean
     */
    protected $adaReferensi;

    /**
     * @var string
     */
    private $namaReferensi;

    /**
     * @ORM\ManyToOne(targetEntity="Referensi", inversedBy="siswa", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="referensi_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     *
     * @var Referensi
     */
    private $referensi;

    /**
     * @var string
     */
    private $namaSekolahAsal;

    /**
     * @ORM\ManyToOne(targetEntity="SekolahAsal", inversedBy="siswa", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="sekolah_asal_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     *
     * @var SekolahAsal
     */
    private $sekolahAsal;

    /**
     * @var boolean
     */
    protected $tentukanPenjurusan;

    /**
     * @ORM\ManyToOne(targetEntity="Penjurusan")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="penjurusan_id", referencedColumnName="id", nullable=true)
     * })
     *
     * @var Penjurusan
     */
    private $penjurusan;

    /**
     * @ORM\OneToMany(targetEntity="OrangtuaWali", mappedBy="siswa", cascade={"persist", "remove"})
     * @ORM\OrderBy({"aktif" = "DESC"})
     *
     * @var OrangtuaWali
     */
    private $orangtuaWali;

    /**
     * @ORM\OneToMany(targetEntity="DokumenSiswa", mappedBy="siswa", cascade={"persist", "remove"})
     *
     * @var DokumenSiswa
     */
    private $dokumenSiswa;

    /**
     * @ORM\OneToMany(targetEntity="PendidikanSiswa", mappedBy="siswa", cascade={"persist", "remove"})
     *
     * @var PendidikanSiswa
     */
    private $pendidikanSiswa;

    /**
     * @ORM\OneToMany(targetEntity="PenyakitSiswa", mappedBy="siswa", cascade={"persist", "remove"})
     *
     * @var PenyakitSiswa
     */
    private $penyakitSiswa;

    /**
     * @ORM\OneToMany(targetEntity="PembayaranPendaftaran", mappedBy="siswa")
     *
     * @var PembayaranPendaftaran
     */
    private $pembayaranPendaftaran;

    /**
     * @ORM\OneToMany(targetEntity="PembayaranSekali", mappedBy="siswa")
     *
     * @var PembayaranSekali
     */
    private $pembayaranSekali;

    /**
     * @ORM\OneToMany(targetEntity="PembayaranRutin", mappedBy="siswa")
     *
     * @var PembayaranRutin
     */
    private $pembayaranRutin;

    /**
     * @ORM\OneToMany(targetEntity="SiswaKelas", mappedBy="siswa")
     *
     * @var SiswaKelas
     */
    private $siswaKelas;

    public function __construct()
    {
        $this->orangtuaWali = new ArrayCollection();
        $this->pembayaranPendaftaran = new ArrayCollection();
        $this->pembayaranSekali = new ArrayCollection();
        $this->pembayaranRutin = new ArrayCollection();
        $this->siswaKelas = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $nomorPendaftaran
     */
    public function setNomorUrutPendaftaran($nomorUrutPendaftaran)
    {
        $this->nomorUrutPendaftaran = $nomorUrutPendaftaran;
    }

    /**
     * @return integer
     */
    public function getNomorUrutPendaftaran()
    {
        return $this->nomorUrutPendaftaran;
    }

    /**
     * @param string $nomorPendaftaran
     */
    public function setNomorPendaftaran($nomorPendaftaran)
    {
        $this->nomorPendaftaran = $nomorPendaftaran;
    }

    /**
     * @return string
     */
    public function getNomorPendaftaran()
    {
        return $this->nomorPendaftaran;
    }

    /**
     * @param boolean $calonSiswa
     */
    public function setCalonSiswa($calonSiswa)
    {
        $this->calonSiswa = $calonSiswa;
    }

    /**
     * @return boolean
     */
    public function isCalonSiswa()
    {
        return $this->calonSiswa;
    }

    /**
     * @param integer $nomorUrutPersekolah
     */
    public function setNomorUrutPersekolah($nomorUrutPersekolah)
    {
        $this->nomorUrutPersekolah = $nomorUrutPersekolah;
    }

    /**
     * @return integer
     */
    public function getNomorUrutPersekolah()
    {
        return $this->nomorUrutPersekolah;
    }

    /**
     * @param string $nomorIndukSistem
     */
    public function setNomorIndukSistem($nomorIndukSistem)
    {
        $this->nomorIndukSistem = $nomorIndukSistem;
    }

    /**
     * @return string
     */
    public function getNomorIndukSistem()
    {
        return $this->nomorIndukSistem;
    }

    /**
     * @param string $nomorInduk
     */
    public function setNomorInduk($nomorInduk)
    {
        $this->nomorInduk = $nomorInduk;
    }

    /**
     * @return string
     */
    public function getNomorInduk()
    {
        return $this->nomorInduk;
    }

    /**
     * @param string $namaLengkap
     */
    public function setNamaLengkap($namaLengkap)
    {
        $this->namaLengkap = $namaLengkap;
    }

    /**
     * @return string
     */
    public function getNamaLengkap()
    {
        return $this->namaLengkap;
    }

    /**
     * @param string $jenisKelamin
     */
    public function setJenisKelamin($jenisKelamin)
    {
        $this->jenisKelamin = $jenisKelamin;
    }

    /**
     * @return string
     */
    public function getJenisKelamin()
    {
        return $this->jenisKelamin;
    }

    /**
     * @param string $fotoPendaftaran
     */
    public function setFotoPendaftaran($fotoPendaftaran)
    {
        $this->fotoPendaftaran = $fotoPendaftaran;
    }

    /**
     * @return string
     */
    public function getFotoPendaftaran()
    {
        return $this->fotoPendaftaran;
    }

    /**
     * @param string $foto
     */
    public function setFoto($foto)
    {
        $this->foto = $foto;
    }

    /**
     * @return string
     */
    public function getFoto()
    {
        return $this->foto;
    }

    /**
     * @param string $fotoDisk
     */
    public function setFotoDisk($fotoDisk)
    {
        $this->fotoDisk = $fotoDisk;
    }

    /**
     * @return string
     */
    public function getFotoDisk()
    {
        return $this->fotoDisk;
    }

    /**
     * @param string $agama
     */
    public function setAgama($agama)
    {
        $this->agama = $agama;
    }

    /**
     * @return string
     */
    public function getAgama()
    {
        return $this->agama;
    }

    /**
     * @param string $tempatLahir
     */
    public function setTempatLahir($tempatLahir)
    {
        $this->tempatLahir = $tempatLahir;
    }

    /**
     * @return string
     */
    public function getTempatLahir()
    {
        return $this->tempatLahir;
    }

    /**
     * @param \DateTime $tanggalLahir
     */
    public function setTanggalLahir($tanggalLahir)
    {
        $this->tanggalLahir = $tanggalLahir;
    }

    /**
     * @return \DateTime
     */
    public function getTanggalLahir()
    {
        return $this->tanggalLahir;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $namaPanggilan
     */
    public function setNamaPanggilan($namaPanggilan)
    {
        $this->namaPanggilan = $namaPanggilan;
    }

    /**
     * @return string
     */
    public function getNamaPanggilan()
    {
        return $this->namaPanggilan;
    }

    /**
     * @param string $kewarganegaraan
     */
    public function setKewarganegaraan($kewarganegaraan)
    {
        $this->kewarganegaraan = $kewarganegaraan;
    }

    /**
     * @return string
     */
    public function getKewarganegaraan()
    {
        return $this->kewarganegaraan;
    }

    /**
     * @param integer $anakKe
     */
    public function setAnakKe($anakKe)
    {
        $this->anakKe = $anakKe;
    }

    /**
     * @return integer
     */
    public function getAnakKe()
    {
        return $this->anakKe;
    }

    /**
     * @param integer $jumlahSaudarakandung
     */
    public function setJumlahSaudarakandung($jumlahSaudarakandung)
    {
        $this->jumlahSaudarakandung = $jumlahSaudarakandung;
    }

    /**
     * @return integer
     */
    public function getJumlahSaudarakandung()
    {
        return $this->jumlahSaudarakandung;
    }

    /**
     * @param integer $jumlahSaudaratiri
     */
    public function setJumlahSaudaratiri($jumlahSaudaratiri)
    {
        $this->jumlahSaudaratiri = $jumlahSaudaratiri;
    }

    /**
     * @return integer
     */
    public function getJumlahSaudaratiri()
    {
        return $this->jumlahSaudaratiri;
    }

    /**
     * @param string $statusOrphan
     */
    public function setStatusOrphan($statusOrphan)
    {
        $this->statusOrphan = $statusOrphan;
    }

    /**
     * @return string
     */
    public function getStatusOrphan()
    {
        return $this->statusOrphan;
    }

    /**
     * @param string $bahasaSeharihari
     */
    public function setBahasaSeharihari($bahasaSeharihari)
    {
        $this->bahasaSeharihari = $bahasaSeharihari;
    }

    /**
     * @return string
     */
    public function getBahasaSeharihari()
    {
        return $this->bahasaSeharihari;
    }

    /**
     * @param string $alamat
     */
    public function setAlamat($alamat)
    {
        $this->alamat = $alamat;
    }

    /**
     * @return string
     */
    public function getAlamat()
    {
        return $this->alamat;
    }

    /**
     * @param string $kodepos
     */
    public function setKodepos($kodepos)
    {
        $this->kodepos = $kodepos;
    }

    /**
     * @return string
     */
    public function getKodepos()
    {
        return $this->kodepos;
    }

    /**
     * @param string $telepon
     */
    public function setTelepon($telepon)
    {
        $this->telepon = $telepon;
    }

    /**
     * @return string
     */
    public function getTelepon()
    {
        return $this->telepon;
    }

    /**
     * @param string $ponselSiswa
     */
    public function setPonselSiswa($ponselSiswa)
    {
        $this->ponselSiswa = $ponselSiswa;
    }

    /**
     * @return string
     */
    public function getPonselSiswa()
    {
        return $this->ponselSiswa;
    }

    /**
     * @param string $sekolahTinggaldi
     */
    public function setSekolahTinggaldi($sekolahTinggaldi)
    {
        $this->sekolahTinggaldi = $sekolahTinggaldi;
    }

    /**
     * @return string
     */
    public function getSekolahTinggaldi()
    {
        return $this->sekolahTinggaldi;
    }

    /**
     * @param string $jarakTempat
     */
    public function setJarakTempat($jarakTempat)
    {
        $this->jarakTempat = $jarakTempat;
    }

    /**
     * @return string
     */
    public function getJarakTempat()
    {
        return $this->jarakTempat;
    }

    /**
     * @param string $caraKesekolah
     */
    public function setCaraKesekolah($caraKesekolah)
    {
        $this->caraKesekolah = $caraKesekolah;
    }

    /**
     * @return string
     */
    public function getCaraKesekolah()
    {
        return $this->caraKesekolah;
    }

    /**
     * @param string $beratbadan
     */
    public function setBeratbadan($beratbadan)
    {
        $this->beratbadan = $beratbadan;
    }

    /**
     * @return string
     */
    public function getBeratbadan()
    {
        return $this->beratbadan;
    }

    /**
     * @param string $tinggibadan
     */
    public function setTinggibadan($tinggibadan)
    {
        $this->tinggibadan = $tinggibadan;
    }

    /**
     * @return string
     */
    public function getTinggibadan()
    {
        return $this->tinggibadan;
    }

    /**
     * @param string $golongandarah
     */
    public function setGolongandarah($golongandarah)
    {
        $this->golongandarah = $golongandarah;
    }

    /**
     * @return string
     */
    public function getGolongandarah()
    {
        return $this->golongandarah;
    }

    /**
     * @param boolean $lunasBiayaPendaftaran
     */
    public function setLunasBiayaPendaftaran($lunasBiayaPendaftaran)
    {
        $this->lunasBiayaPendaftaran = $lunasBiayaPendaftaran;
    }

    /**
     * @return boolean
     */
    public function isLunasBiayaPendaftaran()
    {
        return $this->lunasBiayaPendaftaran;
    }

    /**
     * @param boolean $sisaBiayaPendaftaran
     */
    public function setSisaBiayaPendaftaran($sisaBiayaPendaftaran)
    {
        $this->sisaBiayaPendaftaran = $sisaBiayaPendaftaran;
    }

    /**
     * @return integer
     */
    public function getSisaBiayaPendaftaran()
    {
        return $this->sisaBiayaPendaftaran;
    }

    /**
     * @param boolean $lunasBiayaSekali
     */
    public function setLunasBiayaSekali($lunasBiayaSekali)
    {
        $this->lunasBiayaSekali = $lunasBiayaSekali;
    }

    /**
     * @return boolean
     */
    public function isLunasBiayaSekali()
    {
        return $this->lunasBiayaSekali;
    }

    /**
     * @param boolean $sisaBiayaSekali
     */
    public function setSisaBiayaSekali($sisaBiayaSekali)
    {
        $this->sisaBiayaSekali = $sisaBiayaSekali;
    }

    /**
     * @return integer
     */
    public function getSisaBiayaSekali()
    {
        return $this->sisaBiayaSekali;
    }

    /**
     * @param \DateTime $waktuSimpan
     */
    public function setWaktuSimpan($waktuSimpan)
    {
        $this->waktuSimpan = $waktuSimpan;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuSimpan()
    {
        return $this->waktuSimpan;
    }

    /**
     * @param \DateTime $waktuUbah
     */
    public function setWaktuUbah($waktuUbah)
    {
        $this->waktuUbah = $waktuUbah;
    }

    /**
     * @return \DateTime
     */
    public function getWaktuUbah()
    {
        return $this->waktuUbah;
    }

    /**
     * @param string $keterangan
     */
    public function setKeterangan($keterangan)
    {
        $this->keterangan = $keterangan;
    }

    /**
     * @return string
     */
    public function getKeterangan()
    {
        return $this->keterangan;
    }

    /**
     * @param Gelombang $gelombang
     */
    public function setGelombang(Gelombang $gelombang = null)
    {
        $this->gelombang = $gelombang;
    }

    /**
     * @return Gelombang
     */
    public function getGelombang()
    {
        return $this->gelombang;
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
     * @param User $dibuatOleh
     */
    public function setDibuatOleh(User $dibuatOleh = null)
    {
        $this->dibuatOleh = $dibuatOleh;
    }

    /**
     * @return User
     */
    public function getDibuatOleh()
    {
        return $this->dibuatOleh;
    }

    /**
     * @param User $diubahOleh
     */
    public function setDiubahOleh(User $diubahOleh = null)
    {
        $this->diubahOleh = $diubahOleh;
    }

    /**
     * @return User
     */
    public function getDiubahOleh()
    {
        return $this->diubahOleh;
    }

    /**
     * @param boolean $adaReferensi
     */
    public function setAdaReferensi($adaReferensi)
    {
        $this->adaReferensi = $adaReferensi;
    }

    /**
     * @return boolean
     */
    public function isAdaReferensi()
    {
        return $this->adaReferensi;
    }

    /**
     * @param string $namaReferensi
     */
    public function setNamaReferensi($namaReferensi)
    {
        $this->namaReferensi = $namaReferensi;
    }

    /**
     * @return string
     */
    public function getNamaReferensi()
    {
        return $this->namaReferensi;
    }

    /**
     * @param Referensi $referensi
     */
    public function setReferensi(Referensi $referensi = null)
    {
        $this->referensi = $referensi;
    }

    /**
     * @return Referensi
     */
    public function getReferensi()
    {
        return $this->referensi;
    }

    /**
     * @param string $namaSekolahAsal
     */
    public function setNamaSekolahAsal($namaSekolahAsal)
    {
        $this->namaSekolahAsal = $namaSekolahAsal;
    }

    /**
     * @return string
     */
    public function getNamaSekolahAsal()
    {
        return $this->namaSekolahAsal;
    }

    /**
     * @param SekolahAsal $sekolahAsal
     */
    public function setSekolahAsal(SekolahAsal $sekolahAsal = null)
    {
        $this->sekolahAsal = $sekolahAsal;
    }

    /**
     * @return SekolahAsal
     */
    public function getSekolahAsal()
    {
        return $this->sekolahAsal;
    }

    /**
     * @param boolean $tentukanPenjurusan
     */
    public function setTentukanPenjurusan($tentukanPenjurusan)
    {
        $this->tentukanPenjurusan = $tentukanPenjurusan;
    }

    /**
     * @return boolean
     */
    public function isTentukanPenjurusan()
    {
        return $this->tentukanPenjurusan;
    }

    /**
     * @param Penjurusan $penjurusan
     */
    public function setPenjurusan(Penjurusan $penjurusan = null)
    {
        $this->penjurusan = $penjurusan;
    }

    /**
     * @return Penjurusan
     */
    public function getPenjurusan()
    {
        return $this->penjurusan;
    }

    /**
     * @param ArrayCollection $orangtuaWali
     */
    public function setOrangtuaWali(ArrayCollection $orangtuaWali)
    {
        foreach ($orangtuaWali as $data) {
            $data->setSiswa($this);
        }

        $this->orangtuaWali = $orangtuaWali;
    }

    /**
     * @return ArrayCollection $orangtuaWali
     */
    public function getOrangtuaWali()
    {
        return $this->orangtuaWali;
    }

    /**
     * @return OrangtuaWali|NULL
     */
    public function getOrangtuaWaliAktif()
    {
        foreach ($this->orangtuaWali as $ortu) {
            if ($ortu->isAktif()) {
                return $ortu;
            }
        }

        return;
    }

    /**
     * @param ArrayCollection $siswaKelas
     */
    public function setSiswaKelas(ArrayCollection $siswaKelas)
    {
        foreach ($siswaKelas as $data) {
            $data->setSiswa($this);
        }

        $this->siswaKelas = $siswaKelas;
    }

    /**
     * @return SiswaKelas
     */
    public function getSiswaKelas()
    {
        return $this->siswaKelas;
    }

    /**
     * @return SiswaKelas|NULL
     */
    public function getSiswaKelasAktif()
    {
        foreach ($this->getSiswaKelas() as $siswakelas) {
            if ($siswakelas->getTahunAkademik()->getAktif() === true && $siswakelas->getAktif()) {
                return $siswakelas;
            }
        }

        return;
    }

    /**
     * @return PembayaranPendaftaran
     */
    public function getPembayaranPendaftaran()
    {
        return $this->pembayaranPendaftaran;
    }

    /**
     * @return array of DaftarBiayaPendaftaran
     */
    public function getDaftarBiayaPendaftaran()
    {
        $daftar = [];

        foreach ($this->getPembayaranPendaftaran() as $pembayaran) {
            $daftar[] = $pembayaran->getDaftarBiayaPendaftaran();
        }

        return $daftar;
    }

    /**
     * @return array of TransaksiPembayaranPendaftaran
     */
    public function getTransaksiPembayaranPendaftaran()
    {
        $daftar = [];

        foreach ($this->getPembayaranPendaftaran() as $pembayaran) {
            $daftar[] = $pembayaran->getTransaksiPembayaranPendaftaran();
        }

        return $daftar;
    }

    /**
     * @return integer
     */
    public function getTotalNominalBiayaPendaftaran()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranPendaftaran() as $pembayaran) {
            foreach ($pembayaran->getDaftarBiayaPendaftaran() as $daftar) {
                $jumlah += $daftar->getNominal();
            }
        }

        return $jumlah;
    }

    /**
     * @return integer
     */
    public function getTotalNominalPembayaranPendaftaran()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranPendaftaran() as $pembayaran) {
            foreach ($pembayaran->getTransaksiPembayaranPendaftaran() as $transaksi) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }

    /**
     * @return integer
     */
    public function getTotalPotonganPembayaranPendaftaran()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranPendaftaran() as $pembayaran) {
            $jumlah += $pembayaran->getNominalPotongan() + $pembayaran->getPersenPotonganDinominalkan();
        }

        return $jumlah;
    }

    /**
     * @return PembayaranSekali
     */
    public function getPembayaranSekali()
    {
        return $this->pembayaranSekali;
    }

    /**
     * @return array of DaftarBiayaSekali
     */
    public function getDaftarBiayaSekali()
    {
        $daftar = [];

        foreach ($this->getPembayaranSekali() as $pembayaran) {
            $daftar[] = $pembayaran->getDaftarBiayaSekali();
        }

        return $daftar;
    }

    /**
     * @return array of TransaksiPembayaranSekali
     */
    public function getTransaksiPembayaranSekali()
    {
        $daftar = [];

        foreach ($this->getPembayaranSekali() as $pembayaran) {
            $daftar[] = $pembayaran->getTransaksiPembayaranSekali();
        }

        return $daftar;
    }

    /**
     * @return integer
     */
    public function getTotalNominalBiayaSekali()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranSekali() as $pembayaran) {
            foreach ($pembayaran->getDaftarBiayaSekali() as $daftar) {
                $jumlah += $daftar->getNominal();
            }
        }

        return $jumlah;
    }

    /**
     * @return integer
     */
    public function getTotalNominalPembayaranSekali()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranSekali() as $pembayaran) {
            foreach ($pembayaran->getTransaksiPembayaranSekali() as $transaksi) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }

    /**
     * @return integer
     */
    public function getTotalPotonganPembayaranSekali()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranSekali() as $pembayaran) {
            $jumlah += $pembayaran->getNominalPotongan() + $pembayaran->getPersenPotonganDinominalkan();
        }

        return $jumlah;
    }

    /**
     * @return PembayaranRutin
     */
    public function getPembayaranRutin()
    {
        return $this->pembayaranRutin;
    }

    /**
     * @return integer
     */
    public function getTotalNominalPembayaranRutin()
    {
        $jumlah = 0;

        foreach ($this->getPembayaranRutin() as $pembayaran) {
            foreach ($pembayaran->getTransaksiPembayaranRutin() as $transaksi) {
                $jumlah += $transaksi->getNominalPembayaran();
            }
        }

        return $jumlah;
    }

    /**
     * @return string
     */
    public function getWebcamPhotoDir()
    {
        $fs = new Filesystem();
        if (!$fs->exists(self::WEBCAMPHOTO_DIR)) {
            $fs->mkdir(self::WEBCAMPHOTO_DIR);
        }

        return self::WEBCAMPHOTO_DIR;
    }

    /**
     * @return Ambigous <NULL, string>
     */
    public function getWebcamPhotoPath()
    {
        $fs = new Filesystem();
        if (
            !$fs->exists(
                self::WEBCAMPHOTO_DIR
                .$this->getSekolah()->getId()
                .DIRECTORY_SEPARATOR
                .$this->getTahun()->getTahun()
            )
        ) {
            $fs->mkdir(
                self::WEBCAMPHOTO_DIR
                .$this->getSekolah()->getId()
                .DIRECTORY_SEPARATOR
                .$this->getTahun()->getTahun()
            );
        }

        return null === $this->fotoPendaftaran ?
            null : self::WEBCAMPHOTO_DIR
                .$this->getSekolah()->getId()
                .DIRECTORY_SEPARATOR
                .$this->getTahun()->getTahun()
                .DIRECTORY_SEPARATOR
                .$this->fotoPendaftaran
        ;
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file)
    {
        $this->file = $file;
    }

    /**
     * @return NULL|string
     */
    public function getWebPathFotoDisk()
    {
        return null === $this->fotoDisk ? null : $this->getUploadDir().DIRECTORY_SEPARATOR.$this->fotoDisk;
    }

    /**
     * @return NULL|string
     */
    public function getRelativePathFotoDisk()
    {
        return null === $this->fotoDisk ? null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->fotoDisk;
    }

    /**
     * @return NULL|string
     */
    public function getRelativePathFotoDiskSebelumnya()
    {
        return null === $this->fotoDiskSebelumnya ? null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->fotoDiskSebelumnya;
    }

    /**
     * @return NULL|string
     */
    public function getRelativePathFotoDiskThumbSebelumnya()
    {
        return null === $this->fotoDiskSebelumnya ?
            null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->fotoDiskSebelumnya
        ;
    }

    /**
     * @param  string $type
     * @return string
     */
    public function getFilesizeFotoDisk($type = 'KB')
    {
        $file = new File($this->getRelativePathFotoDisk());

        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            $this->fotoDiskSebelumnya = $this->fotoDisk;

            $this->fotoDisk = sha1(uniqid(mt_rand(), true)).'_'.$this->file->getClientOriginalName();

            $this->foto = $this->file->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        if ($this->file->move($this->getUploadRootDir(), $this->fotoDisk)) {
            $targetfile = $this->getAbsolutePath();
            $thumbnailfile = $this->getUploadRootDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->fotoDisk;

            list($origWidth, $origHeight, $type, $attr) = @getimagesize($targetfile);
            if (is_numeric($type)) {
                $origRatio = $origWidth / $origHeight;
                $resultWidth = self::PHOTO_THUMB_WIDTH;
                $resultHeight = self::PHOTO_THUMB_HEIGHT;
                if ($resultWidth / $resultHeight > $origRatio) {
                    $resultWidth = $resultHeight * $origRatio;
                } else {
                    $resultHeight = $resultWidth / $origRatio;
                }

                @ini_set('memory_limit', self::MEMORY_LIMIT);

                switch ($type) {
                    case IMAGETYPE_JPEG:
                        if (imagetypes() & IMG_JPEG) {
                            $resultImage = imagecreatetruecolor($resultWidth, $resultHeight);
                            $srcImage = imagecreatefromjpeg($targetfile);

                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth, $resultHeight, $origWidth, $origHeight);

                            imagejpeg($resultImage, $thumbnailfile, 90);
                        }
                        break;
                    case IMAGETYPE_PNG:
                        if (imagetypes() & IMG_PNG) {
                            $resultImage = imagecreate($resultWidth, $resultHeight);
                            $srcImage = imagecreatefrompng($targetfile);

                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth, $resultHeight, $origWidth, $origHeight);

                            imagepng($resultImage, $thumbnailfile, 8);
                        }
                        break;
                    case IMAGETYPE_GIF:
                        if (imagetypes() & IMG_GIF) {
                            $resultImage = imagecreatetruecolor($resultWidth, $resultHeight);
                            $srcImage = imagecreatefromgif($targetfile);

                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth, $resultHeight, $origWidth, $origHeight);

                            imagegif($resultImage, $thumbnailfile);
                        }
                        break;
                }
            }
        }

        $this->removeFotoSebelumnya();

        unset($this->file);
    }

    private function removeFotoSebelumnya()
    {
        if ($file = $this->getRelativePathFotoDiskSebelumnya()) {
            @unlink($file);
        }
        if ($fileThumb = $this->getRelativePathFotoDiskThumbSebelumnya()) {
            @unlink($fileThumb);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            @unlink($file);
        }
    }

    /**
     * @return NULL|string
     */
    public function getAbsolutePath()
    {
        return null === $this->fotoDisk ? null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->fotoDisk;
    }

    /**
     * @return NULL|string
     */
    public function getWebPath()
    {
        return null === $this->fotoDisk ? null : $this->getUploadDir().DIRECTORY_SEPARATOR.$this->fotoDisk;
    }

    /**
     * @return NULL|string
     */
    public function getWebPathThumbnail()
    {
        return null === $this->fotoDisk ? null : $this->getUploadDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->fotoDisk;
    }

    /**
     * @return string
     */
    protected function getUploadRootDir()
    {
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        $fs = new Filesystem();
        if (
            !$fs->exists(
                self::PHOTO_DIR
                .$this->getSekolah()->getId()
                .DIRECTORY_SEPARATOR
                .$this->getTahun()->getTahun()
            )
        ) {
            $fs->mkdir(
                self::PHOTO_DIR
                .$this->getSekolah()->getId()
                .DIRECTORY_SEPARATOR
                .$this->getTahun()->getTahun()
            );
        }

        return self::PHOTO_DIR.$this->getSekolah()->getId().DIRECTORY_SEPARATOR.$this->getTahun()->getTahun();
    }
}
