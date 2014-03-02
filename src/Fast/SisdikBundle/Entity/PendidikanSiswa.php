<?php
namespace Fast\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Fast\SisdikBundle\Util\FileSizeFormatter;

/**
 * @ORM\Table(name="pendidikan_siswa")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PendidikanSiswa
{
    const PENDIDIKAN_DIR = 'uploads/students/pendidikan-sebelumnya/';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="jenjang", type="string", length=50, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $jenjang;

    /**
     * @ORM\Column(name="nama", type="string", length=400, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="ijazah_tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $ijazahTanggal;

    /**
     * @ORM\Column(name="ijazah_nomor", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $ijazahNomor;

    /**
     * @ORM\Column(name="ijazah_file", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $ijazahFile;

    /**
     * @ORM\Column(name="ijazah_file_disk", type="string", length=300, nullable=true)
     */
    private $ijazahFileDisk;

    /**
     * @var string
     */
    private $ijazahFileDiskSebelumnya;

    /**
     * @Assert\File(maxSize="5M")
     *
     * @var UploadedFile
     */
    private $fileUploadIjazah;

    /**
     * @ORM\Column(name="tahunmasuk", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $tahunmasuk;

    /**
     * @ORM\Column(name="tahunkeluar", type="string", length=45, nullable=true)
     *
     * @var string
     */
    private $tahunkeluar;

    /**
     * @ORM\Column(name="kelulusan_tanggal", type="date", nullable=true)
     *
     * @var \DateTime
     */
    private $kelulusanTanggal;

    /**
     * @ORM\Column(name="kelulusan_nomor", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $kelulusanNomor;

    /**
     * @ORM\Column(name="kelulusan_file", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $kelulusanFile;

    /**
     * @ORM\Column(name="kelulusan_file_disk", type="string", length=300, nullable=true)
     *
     * @var string
     */
    private $kelulusanFileDisk;

    /**
     * @var string
     */
    private $kelulusanFileDiskSebelumnya;

    /**
     * @Assert\File(maxSize="5M")
     *
     * @var UploadedFile
     */
    private $fileUploadKelulusan;

    /**
     * @ORM\Column(name="keterangan", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $keterangan;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="pendidikanSiswa")
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

    public static function daftarPilihanJenjangSekolah()
    {
        return [
            'a-PAUD/TK' => 'PAUD/TK',
            'b-SD/MI' => 'SD/MI',
            'c-SMP/MTS' => 'SMP/MTS',
            'd-SMA/SMK/ALIYAH' => 'SMA/SMK/ALIYAH'
        ];
    }

    /**
     * @param  string          $jenjang
     */
    public function setJenjang($jenjang)
    {
        $this->jenjang = $jenjang;
    }

    /**
     * @return string
     */
    public function getJenjang()
    {
        return $this->jenjang;
    }

    /**
     * @param  string          $nama
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
     * @param  string          $alamat
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
     * @param  \DateTime       $ijazahTanggal
     */
    public function setIjazahTanggal($ijazahTanggal)
    {
        $this->ijazahTanggal = $ijazahTanggal;
    }

    /**
     * @return \DateTime
     */
    public function getIjazahTanggal()
    {
        return $this->ijazahTanggal;
    }

    /**
     * @param  string          $ijazahNomor
     */
    public function setIjazahNomor($ijazahNomor)
    {
        $this->ijazahNomor = $ijazahNomor;
    }

    /**
     * @return string
     */
    public function getIjazahNomor()
    {
        return $this->ijazahNomor;
    }

    /**
     * @param  string          $ijazahFile
     */
    public function setIjazahFile($ijazahFile)
    {
        $this->ijazahFile = $ijazahFile;
    }

    /**
     * @return string
     */
    public function getIjazahFile()
    {
        return strlen($this->ijazahFile) > 20 ? '...' . substr($this->ijazahFile, -17) : $this->ijazahFile;
    }

    /**
     * @param  string       $ijazahFileDisk
     */
    public function setIjazahFileDisk($ijazahFileDisk)
    {
        $this->ijazahFileDisk = $ijazahFileDisk;
    }

    /**
     * @return string
     */
    public function getIjazahFileDisk()
    {
        return $this->ijazahFileDisk;
    }

    /**
     * @param  string          $tahunmasuk
     */
    public function setTahunmasuk($tahunmasuk)
    {
        $this->tahunmasuk = $tahunmasuk;
    }

    /**
     * @return string
     */
    public function getTahunmasuk()
    {
        return $this->tahunmasuk;
    }

    /**
     * @param  string          $tahunkeluar
     */
    public function setTahunkeluar($tahunkeluar)
    {
        $this->tahunkeluar = $tahunkeluar;
    }

    /**
     * @return string
     */
    public function getTahunkeluar()
    {
        return $this->tahunkeluar;
    }

    /**
     * @param  \DateTime       $kelulusanTanggal
     */
    public function setKelulusanTanggal($kelulusanTanggal)
    {
        $this->kelulusanTanggal = $kelulusanTanggal;
    }

    /**
     * @return \DateTime
     */
    public function getKelulusanTanggal()
    {
        return $this->kelulusanTanggal;
    }

    /**
     * @param  string          $kelulusanNomor
     */
    public function setKelulusanNomor($kelulusanNomor)
    {
        $this->kelulusanNomor = $kelulusanNomor;
    }

    /**
     * @return string
     */
    public function getKelulusanNomor()
    {
        return $this->kelulusanNomor;
    }

    /**
     * @param  string          $kelulusanFile
     */
    public function setKelulusanFile($kelulusanFile)
    {
        $this->kelulusanFile = $kelulusanFile;
    }

    /**
     * @return string
     */
    public function getKelulusanFile()
    {
        return strlen($this->kelulusanFile) > 20 ? '...' . substr($this->kelulusanFile, -17) : $this->kelulusanFile;
    }

    /**
     * @param  string       $kelulusanFileDisk
     */
    public function setKelulusanFileDisk($kelulusanFileDisk)
    {
        $this->kelulusanFileDisk = $kelulusanFileDisk;
    }

    /**
     * @return string
     */
    public function getKelulusanFileDisk()
    {
        return $this->kelulusanFileDisk;
    }

    /**
     * @param  string          $keterangan
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
     * @param  Siswa $siswa
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

    /**
     * @return UploadedFile
     */
    public function getFileUploadIjazah()
    {
        return $this->fileUploadIjazah;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFileUploadIjazah(UploadedFile $file)
    {
        $this->fileUploadIjazah = $file;
    }

    /**
     * @return UploadedFile
     */
    public function getFileUploadKelulusan()
    {
        return $this->fileUploadKelulusan;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFileUploadKelulusan(UploadedFile $file)
    {
        $this->fileUploadKelulusan = $file;
    }

    /**
     * @return NULL|string
     */
    public function getWebPathIjazahFileDisk()
    {
        return null === $this->ijazahFileDisk ?
            null : $this->getUploadDir() . DIRECTORY_SEPARATOR . $this->ijazahFileDisk;
    }

    /**
     * @return NULL|string
     */
    public function getRelativePathIjazahFileDisk()
    {
        return null === $this->ijazahFileDisk ?
            null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->ijazahFileDisk;
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getRelativePathIjazahFileDiskSebelumnya()
    {
        return null === $this->ijazahFileDiskSebelumnya ?
            null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->ijazahFileDiskSebelumnya;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getFilesizeIjazahFileDisk($type = 'KB')
    {
        $file = new File($this->getRelativePathIjazahFileDisk());

        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getWebPathKelulusanFileDisk()
    {
        return null === $this->kelulusanFileDisk ?
            null : $this->getUploadDir() . DIRECTORY_SEPARATOR . $this->kelulusanFileDisk;
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getRelativePathKelulusanFileDisk()
    {
        return null === $this->kelulusanFileDisk ?
            null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->kelulusanFileDisk;
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getRelativePathKelulusanFileDiskSebelumnya()
    {
        return null === $this->kelulusanFileDiskSebelumnya ?
            null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->kelulusanFileDiskSebelumnya;
    }

    /**
     * @param string $type
     * @return string
     */
    public function getFilesizeKelulusanFileDisk($type = 'KB')
    {
        $file = new File($this->getRelativePathKelulusanFileDisk());

        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist()
    {
        if (null !== $this->fileUploadIjazah) {
            $this->ijazahFileDiskSebelumnya = $this->ijazahFileDisk;

            $this->ijazahFileDisk = sha1(uniqid(mt_rand(), true)) . '_' . $this->fileUploadIjazah->getClientOriginalName();

            $this->ijazahFile = $this->fileUploadIjazah->getClientOriginalName();
        }

        if (null !== $this->fileUploadKelulusan) {
            $this->kelulusanFileDiskSebelumnya = $this->kelulusanFileDisk;

            $this->kelulusanFileDisk = sha1(uniqid(mt_rand(), true)) . '_' . $this->fileUploadKelulusan->getClientOriginalName();

            $this->kelulusanFile = $this->fileUploadKelulusan->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postPersist()
    {
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

    private function removeIjazahFileSebelumnya()
    {
        if ($file = $this->getRelativePathIjazahFileDiskSebelumnya()) {
            @unlink($file);
        }
    }

    private function removeKelulusanFileSebelumnya()
    {
        if ($file = $this->getRelativePathKelulusanFileDiskSebelumnya()) {
            @unlink($file);
        }
    }

    /**
     * @return string
     */
    protected function getUploadRootDir()
    {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        $fs = new Filesystem();
        if (
            !$fs->exists(
                self::PENDIDIKAN_DIR
                . $this->getSiswa()->getSekolah()->getId()
                . DIRECTORY_SEPARATOR
                . $this->getSiswa()->getTahun()->getTahun()
            )
        ) {
            $fs->mkdir(
                self::PENDIDIKAN_DIR
                . $this->getSiswa()->getSekolah()->getId()
                . DIRECTORY_SEPARATOR
                . $this->getSiswa()->getTahun()->getTahun()
            );
        }

        return
            self::PENDIDIKAN_DIR
            . $this->getSiswa()->getSekolah()->getId()
            . DIRECTORY_SEPARATOR
            . $this->getSiswa()->getTahun()->getTahun()
        ;
    }
}
