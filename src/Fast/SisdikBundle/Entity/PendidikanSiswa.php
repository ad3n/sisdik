<?php

namespace Fast\SisdikBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Fast\SisdikBundle\Util\FileSizeFormatter;
use Symfony\Component\HttpFoundation\File\File;

/**
 * PendidikanSiswa
 *
 * @ORM\Table(name="pendidikan_siswa")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PendidikanSiswa
{
    const PENDIDIKAN_DIR = 'uploads/students/pendidikan-sebelumnya/';

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
     * @ORM\Column(name="jenjang", type="string", length=50, nullable=true)
     * @Assert\NotBlank
     */
    private $jenjang;

    /**
     * @var string
     *
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     * @Assert\NotBlank
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     * @Assert\NotBlank
     */
    private $alamat;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ijazah_tanggal", type="date", nullable=true)
     */
    private $ijazahTanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="ijazah_nomor", type="string", length=100, nullable=true)
     */
    private $ijazahNomor;

    /**
     * @var string
     *
     * @ORM\Column(name="ijazah_file", type="string", length=300, nullable=true)
     */
    private $ijazahFile;

    /**
     * @var string
     *
     * @ORM\Column(name="ijazah_file_disk", type="string", length=300, nullable=true)
     */
    private $ijazahFileDisk;

    /**
     * @var string
     */
    private $ijazahFileDiskSebelumnya;

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="5M")
     */
    private $fileUploadIjazah;

    /**
     * @var string
     *
     * @ORM\Column(name="tahunmasuk", type="string", length=45, nullable=true)
     */
    private $tahunmasuk;

    /**
     * @var string
     *
     * @ORM\Column(name="tahunkeluar", type="string", length=45, nullable=true)
     */
    private $tahunkeluar;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="kelulusan_tanggal", type="date", nullable=true)
     */
    private $kelulusanTanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="kelulusan_nomor", type="string", length=100, nullable=true)
     */
    private $kelulusanNomor;

    /**
     * @var string
     *
     * @ORM\Column(name="kelulusan_file", type="string", length=300, nullable=true)
     */
    private $kelulusanFile;

    /**
     * @var string
     *
     * @ORM\Column(name="kelulusan_file_disk", type="string", length=300, nullable=true)
     */
    private $kelulusanFileDisk;

    /**
     * @var string
     */
    private $kelulusanFileDiskSebelumnya;

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="5M")
     */
    private $fileUploadKelulusan;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pendidikanSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $siswa;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    public static function daftarPilihanJenjangSekolah() {
        return array(
                'a-PAUD/TK' => 'PAUD/TK', 'b-SD/MI' => 'SD/MI', 'c-SMP/MTS' => 'SMP/MTS',
                'd-SMA/SMK/ALIYAH' => 'SMA/SMK/ALIYAH'
        );
    }

    /**
     * Set jenjang
     *
     * @param string $jenjang
     * @return PendidikanSiswa
     */
    public function setJenjang($jenjang) {
        $this->jenjang = $jenjang;

        return $this;
    }

    /**
     * Get jenjang
     *
     * @return string
     */
    public function getJenjang() {
        return $this->jenjang;
    }

    /**
     * Set nama
     *
     * @param string $nama
     * @return PendidikanSiswa
     */
    public function setNama($nama) {
        $this->nama = $nama;

        return $this;
    }

    /**
     * Get nama
     *
     * @return string
     */
    public function getNama() {
        return $this->nama;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return PendidikanSiswa
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
     * Set ijazahTanggal
     *
     * @param \DateTime $ijazahTanggal
     * @return PendidikanSiswa
     */
    public function setIjazahTanggal($ijazahTanggal) {
        $this->ijazahTanggal = $ijazahTanggal;

        return $this;
    }

    /**
     * Get ijazahTanggal
     *
     * @return \DateTime
     */
    public function getIjazahTanggal() {
        return $this->ijazahTanggal;
    }

    /**
     * Set ijazahNomor
     *
     * @param string $ijazahNomor
     * @return PendidikanSiswa
     */
    public function setIjazahNomor($ijazahNomor) {
        $this->ijazahNomor = $ijazahNomor;

        return $this;
    }

    /**
     * Get ijazahNomor
     *
     * @return string
     */
    public function getIjazahNomor() {
        return $this->ijazahNomor;
    }

    /**
     * Set ijazahFile
     *
     * @param string $ijazahFile
     * @return PendidikanSiswa
     */
    public function setIjazahFile($ijazahFile) {
        $this->ijazahFile = $ijazahFile;

        return $this;
    }

    /**
     * Get ijazahFile
     *
     * @return string
     */
    public function getIjazahFile() {
        if (strlen($this->ijazahFile) > 20) {
            return '...' . substr($this->ijazahFile, -17);
        }
        return $this->ijazahFile;
    }

    /**
     * Set ijazahFileDisk
     *
     * @param string $ijazahFileDisk
     * @return DokumenSiswa
     */
    public function setIjazahFileDisk($ijazahFileDisk) {
        $this->ijazahFileDisk = $ijazahFileDisk;

        return $this;
    }

    /**
     * Get ijazahFileDisk
     *
     * @return string
     */
    public function getIjazahFileDisk() {
        return $this->ijazahFileDisk;
    }

    /**
     * Set tahunmasuk
     *
     * @param string $tahunmasuk
     * @return PendidikanSiswa
     */
    public function setTahunmasuk($tahunmasuk) {
        $this->tahunmasuk = $tahunmasuk;

        return $this;
    }

    /**
     * Get tahunmasuk
     *
     * @return string
     */
    public function getTahunmasuk() {
        return $this->tahunmasuk;
    }

    /**
     * Set tahunkeluar
     *
     * @param string $tahunkeluar
     * @return PendidikanSiswa
     */
    public function setTahunkeluar($tahunkeluar) {
        $this->tahunkeluar = $tahunkeluar;

        return $this;
    }

    /**
     * Get tahunkeluar
     *
     * @return string
     */
    public function getTahunkeluar() {
        return $this->tahunkeluar;
    }

    /**
     * Set kelulusanTanggal
     *
     * @param \DateTime $kelulusanTanggal
     * @return PendidikanSiswa
     */
    public function setKelulusanTanggal($kelulusanTanggal) {
        $this->kelulusanTanggal = $kelulusanTanggal;

        return $this;
    }

    /**
     * Get kelulusanTanggal
     *
     * @return \DateTime
     */
    public function getKelulusanTanggal() {
        return $this->kelulusanTanggal;
    }

    /**
     * Set kelulusanNomor
     *
     * @param string $kelulusanNomor
     * @return PendidikanSiswa
     */
    public function setKelulusanNomor($kelulusanNomor) {
        $this->kelulusanNomor = $kelulusanNomor;

        return $this;
    }

    /**
     * Get kelulusanNomor
     *
     * @return string
     */
    public function getKelulusanNomor() {
        return $this->kelulusanNomor;
    }

    /**
     * Set kelulusanFile
     *
     * @param string $kelulusanFile
     * @return PendidikanSiswa
     */
    public function setKelulusanFile($kelulusanFile) {
        $this->kelulusanFile = $kelulusanFile;

        return $this;
    }

    /**
     * Get kelulusanFile
     *
     * @return string
     */
    public function getKelulusanFile() {
        if (strlen($this->kelulusanFile) > 20) {
            return '...' . substr($this->kelulusanFile, -17);
        }
        return $this->kelulusanFile;
    }

    /**
     * Set kelulusanFileDisk
     *
     * @param string $kelulusanFileDisk
     * @return DokumenSiswa
     */
    public function setKelulusanFileDisk($kelulusanFileDisk) {
        $this->kelulusanFileDisk = $kelulusanFileDisk;

        return $this;
    }

    /**
     * Get kelulusanFileDisk
     *
     * @return string
     */
    public function getKelulusanFileDisk() {
        return $this->kelulusanFileDisk;
    }

    /**
     * Set keterangan
     *
     * @param string $keterangan
     * @return PendidikanSiswa
     */
    public function setKeterangan($keterangan) {
        $this->keterangan = $keterangan;

        return $this;
    }

    /**
     * Get keterangan
     *
     * @return string
     */
    public function getKeterangan() {
        return $this->keterangan;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return PendidikanSiswa
     */
    public function setSiswa(\Fast\SisdikBundle\Entity\Siswa $siswa = null) {
        $this->siswa = $siswa;

        return $this;
    }

    /**
     * Get siswa
     *
     * @return \Fast\SisdikBundle\Entity\Siswa
     */
    public function getSiswa() {
        return $this->siswa;
    }

    public function getFileUploadIjazah() {
        return $this->fileUploadIjazah;
    }

    public function setFileUploadIjazah(UploadedFile $file) {
        $this->fileUploadIjazah = $file;

        return $this;
    }

    public function getFileUploadKelulusan() {
        return $this->fileUploadKelulusan;
    }

    public function setFileUploadKelulusan(UploadedFile $file) {
        $this->fileUploadKelulusan = $file;

        return $this;
    }

    public function getWebPathIjazahFileDisk() {
        return null === $this->ijazahFileDisk ? null : $this->getUploadDir() . '/' . $this->ijazahFileDisk;
    }

    public function getRelativePathIjazahFileDisk() {
        return null === $this->ijazahFileDisk ? null
                : $this->getUploadRootDir() . '/' . $this->ijazahFileDisk;
    }

    public function getRelativePathIjazahFileDiskSebelumnya() {
        return null === $this->ijazahFileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . $this->ijazahFileDiskSebelumnya;
    }

    public function getFilesizeIjazahFileDisk($type = 'KB') {
        $file = new File($this->getRelativePathIjazahFileDisk());
        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    public function getWebPathKelulusanFileDisk() {
        return null === $this->kelulusanFileDisk ? null
                : $this->getUploadDir() . '/' . $this->kelulusanFileDisk;
    }

    public function getRelativePathKelulusanFileDisk() {
        return null === $this->kelulusanFileDisk ? null
                : $this->getUploadRootDir() . '/' . $this->kelulusanFileDisk;
    }

    public function getRelativePathKelulusanFileDiskSebelumnya() {
        return null === $this->kelulusanFileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . $this->kelulusanFileDiskSebelumnya;
    }

    public function getFilesizeKelulusanFileDisk($type = 'KB') {
        $file = new File($this->getRelativePathKelulusanFileDisk());
        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist() {
        if (null !== $this->fileUploadIjazah) {
            $this->ijazahFileDiskSebelumnya = $this->ijazahFileDisk;

            $this->ijazahFileDisk = sha1(uniqid(mt_rand(), true)) . '_'
                    . $this->fileUploadIjazah->getClientOriginalName();
            $this->ijazahFile = $this->fileUploadIjazah->getClientOriginalName();
        }

        if (null !== $this->fileUploadKelulusan) {
            $this->kelulusanFileDiskSebelumnya = $this->kelulusanFileDisk;

            $this->kelulusanFileDisk = sha1(uniqid(mt_rand(), true)) . '_'
                    . $this->fileUploadKelulusan->getClientOriginalName();
            $this->kelulusanFile = $this->fileUploadKelulusan->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postPersist() {
        if ($this->fileUploadIjazah !== null) {
            $this->fileUploadIjazah->move($this->getUploadRootDir(), $this->ijazahFileDisk);
            $this->removeIjazahFileSebelumnya();
            unset($this->fileUploadIjazah);
        }

        if ($this->fileUploadKelulusan !== null) {
            $this->fileUploadKelulusan->move($this->getUploadRootDir(), $this->kelulusanFileDisk);
            $this->removeKelulusanFileSebelumnya();
            unset($this->fileUploadKelulusan);
        }
    }

    private function removeIjazahFileSebelumnya() {
        if ($file = $this->getRelativePathIjazahFileDiskSebelumnya()) {
            @unlink($file);
        }
    }

    private function removeKelulusanFileSebelumnya() {
        if ($file = $this->getRelativePathKelulusanFileDiskSebelumnya()) {
            @unlink($file);
        }
    }

    protected function getUploadRootDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        $fs = new Filesystem();
        if (!$fs
                ->exists(
                        self::PENDIDIKAN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                                . $this->getSiswa()->getTahun()->getTahun())) {
            $fs
                    ->mkdir(
                            self::PENDIDIKAN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                                    . $this->getSiswa()->getTahun()->getTahun());
        }
        return self::PENDIDIKAN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                . $this->getSiswa()->getTahun()->getTahun();
    }
}
