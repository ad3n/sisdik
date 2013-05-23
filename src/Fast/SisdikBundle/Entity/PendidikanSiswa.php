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
     * @ORM\Column(name="sttb_tanggal", type="date", nullable=true)
     */
    private $sttbTanggal;

    /**
     * @var string
     *
     * @ORM\Column(name="sttb_nomor", type="string", length=100, nullable=true)
     */
    private $sttbNomor;

    /**
     * @var string
     *
     * @ORM\Column(name="sttb_file", type="string", length=300, nullable=true)
     */
    private $sttbFile;

    /**
     * @var string
     *
     * @ORM\Column(name="sttb_file_disk", type="string", length=300, nullable=true)
     */
    private $sttbFileDisk;

    /**
     * @var string
     */
    private $sttbFileDiskSebelumnya;

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="5M")
     */
    private $fileUploadSttb;

    /**
     * @var string
     *
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     */
    private $keterangan;

    /**
     * @var \Siswa
     *
     * @ORM\ManyToOne(targetEntity="Siswa")
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
     * Set sttbTanggal
     *
     * @param \DateTime $sttbTanggal
     * @return PendidikanSiswa
     */
    public function setSttbTanggal($sttbTanggal) {
        $this->sttbTanggal = $sttbTanggal;

        return $this;
    }

    /**
     * Get sttbTanggal
     *
     * @return \DateTime
     */
    public function getSttbTanggal() {
        return $this->sttbTanggal;
    }

    /**
     * Set sttbNomor
     *
     * @param string $sttbNomor
     * @return PendidikanSiswa
     */
    public function setSttbNomor($sttbNomor) {
        $this->sttbNomor = $sttbNomor;

        return $this;
    }

    /**
     * Get sttbNomor
     *
     * @return string
     */
    public function getSttbNomor() {
        return $this->sttbNomor;
    }

    /**
     * Set sttbFile
     *
     * @param string $sttbFile
     * @return PendidikanSiswa
     */
    public function setSttbFile($sttbFile) {
        $this->sttbFile = $sttbFile;

        return $this;
    }

    /**
     * Get sttbFile
     *
     * @return string
     */
    public function getSttbFile() {
        if (strlen($this->sttbFile) > 20) {
            return '...' . substr($this->sttbFile, -17);
        }
        return $this->sttbFile;
    }

    /**
     * Set sttbFileDisk
     *
     * @param string $sttbFileDisk
     * @return DokumenSiswa
     */
    public function setSttbFileDisk($sttbFileDisk) {
        $this->sttbFileDisk = $sttbFileDisk;

        return $this;
    }

    /**
     * Get sttbFileDisk
     *
     * @return string
     */
    public function getSttbFileDisk() {
        return $this->sttbFileDisk;
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

    public function getFileUploadSttb() {
        return $this->fileUploadSttb;
    }

    public function setFileUploadSttb(UploadedFile $file) {
        $this->fileUploadSttb = $file;

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

    public function getWebPathSttbFileDisk() {
        return null === $this->sttbFileDisk ? null : $this->getUploadDir() . '/' . $this->sttbFileDisk;
    }

    public function getRelativePathSttbFileDisk() {
        return null === $this->sttbFileDisk ? null : $this->getUploadRootDir() . '/' . $this->sttbFileDisk;
    }

    public function getRelativePathSttbFileDiskSebelumnya() {
        return null === $this->sttbFileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . $this->sttbFileDiskSebelumnya;
    }

    public function getFilesizeSttbFileDisk($type = 'KB') {
        $file = new File($this->getRelativePathSttbFileDisk());
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

        if (null !== $this->fileUploadSttb) {
            $this->sttbFileDiskSebelumnya = $this->sttbFileDisk;

            $this->sttbFileDisk = sha1(uniqid(mt_rand(), true)) . '_'
                    . $this->fileUploadSttb->getClientOriginalName();
            $this->sttbFile = $this->fileUploadSttb->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postPersist() {
        if ($this->fileUploadIjazah === null || $this->fileUploadSttb === null) {
            return;
        }

        $this->fileUploadIjazah->move($this->getUploadRootDir(), $this->ijazahFileDisk);
        $this->fileUploadSttb->move($this->getUploadRootDir(), $this->sttbFileDisk);

        $this->removeIjazahFileSebelumnya();
        $this->removeSttbFileSebelumnya();

        unset($this->fileUploadIjazah);
        unset($this->fileUploadSttb);
    }

    private function removeIjazahFileSebelumnya() {
        if ($file = $this->getRelativePathIjazahFileDiskSebelumnya()) {
            unlink($file);
        }
    }

    private function removeSttbFileSebelumnya() {
        if ($file = $this->getRelativePathSttbFileDiskSebelumnya()) {
            unlink($file);
        }
    }

    protected function getUploadRootDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        $fs = new Filesystem();
        if (!$fs->exists(self::PENDIDIKAN_DIR)) {
            $fs->mkdir(self::PENDIDIKAN_DIR);
        }
        if (!$fs->exists(self::PENDIDIKAN_DIR . $this->getSiswa()->getSekolah()->getId())) {
            $fs->mkdir(self::PENDIDIKAN_DIR . $this->getSiswa()->getSekolah()->getId());
        }
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
