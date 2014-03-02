<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orangtua_wali")
 * @ORM\Entity
 */
class OrangtuaWali
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
     * @ORM\Column(name="nama", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="tempat_lahir", type="string", length=300, nullable=true)
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
     * @ORM\Column(name="ponsel", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $ponsel;

    /**
     * @ORM\Column(name="kewarganegaraan", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $kewarganegaraan;

    /**
     * @ORM\Column(name="hubungan_dengan_siswa", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $hubunganDenganSiswa;

    /**
     * @ORM\Column(name="pendidikan_tertinggi", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $pendidikanTertinggi;

    /**
     * @ORM\Column(name="pekerjaan", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $pekerjaan;

    /**
     * @ORM\Column(name="penghasilan_bulanan", type="integer", nullable=true)
     *
     * @var integer
     */
    private $penghasilanBulanan;

    /**
     * @ORM\Column(name="penghasilan_tahunan", type="integer", nullable=true)
     *
     * @var integer
     */
    private $penghasilanTahunan;

    /**
     * @ORM\Column(name="alamat", type="string", length=400, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\Column(name="aktif", type="boolean", nullable=false, options={"default"=1})
     *
     * @var boolean
     */
    private $aktif = true;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="orangtuaWali")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $nama
     */
    public function setNama($nama)
    {
        $this->nama = $nama;
    }

    /**
     * @return string
     */
    public function getNama()
    {
        return $this->nama;
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
     * @param string $ponsel
     */
    public function setPonsel($ponsel)
    {
        $this->ponsel = $ponsel;
    }

    /**
     * @return string
     */
    public function getPonsel()
    {
        return $this->ponsel;
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
     * @param string $hubunganDenganSiswa
     */
    public function setHubunganDenganSiswa($hubunganDenganSiswa)
    {
        $this->hubunganDenganSiswa = $hubunganDenganSiswa;
    }

    /**
     * @return string
     */
    public function getHubunganDenganSiswa()
    {
        return $this->hubunganDenganSiswa;
    }

    /**
     * @param string $pendidikanTertinggi
     */
    public function setPendidikanTertinggi($pendidikanTertinggi)
    {
        $this->pendidikanTertinggi = $pendidikanTertinggi;
    }

    /**
     * @return string
     */
    public function getPendidikanTertinggi()
    {
        return $this->pendidikanTertinggi;
    }

    /**
     * @param string $pekerjaan
     */
    public function setPekerjaan($pekerjaan)
    {
        $this->pekerjaan = $pekerjaan;
    }

    /**
     * @return string
     */
    public function getPekerjaan()
    {
        return $this->pekerjaan;
    }

    /**
     * @param integer $penghasilanBulanan
     */
    public function setPenghasilanBulanan($penghasilanBulanan)
    {
        $this->penghasilanBulanan = $penghasilanBulanan;
    }

    /**
     * @return integer
     */
    public function getPenghasilanBulanan()
    {
        return $this->penghasilanBulanan;
    }

    /**
     * @param integer $penghasilanTahunan
     */
    public function setPenghasilanTahunan($penghasilanTahunan)
    {
        $this->penghasilanTahunan = $penghasilanTahunan;
    }

    /**
     * @return integer
     */
    public function getPenghasilanTahunan()
    {
        return $this->penghasilanTahunan;
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
     * @param boolean $aktif
     */
    public function setAktif($aktif)
    {
        $this->aktif = $aktif;
    }

    /**
     * @return boolean
     */
    public function isAktif()
    {
        return $this->aktif;
    }

    /**
     * @param Siswa $siswa
     */
    public function setSiswa(Siswa $siswa = null)
    {
        $this->siswa = $siswa;
    }

    /**
     * @return Siswa
     */
    public function getSiswa()
    {
        return $this->siswa;
    }
}
