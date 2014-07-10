<?php
namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Langgas\SisdikBundle\Util\FileSizeFormatter;

/**
 * @ORM\Table(name="dokumen_siswa", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="dokumen_siswa_UNIQUE", columns={"siswa_id", "jenis_dokumen_siswa_id"})
 * })
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DokumenSiswa
{
    const DOKUMEN_DIR = 'uploads/students/dokumen/';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="lengkap", type="boolean", nullable=false, options={"default"=0})
     *
     * @var boolean
     */
    private $lengkap = false;

    /**
     * @ORM\Column(name="nama_file", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $namaFile;

    /**
     * @ORM\Column(name="nama_file_disk", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $namaFileDisk;

    /**
     * @ORM\ManyToOne(targetEntity="JenisDokumenSiswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="jenis_dokumen_siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var JenisDokumenSiswa
     */
    private $jenisDokumenSiswa;

    /**
     * @ORM\ManyToOne(targetEntity="Siswa", inversedBy="dokumenSiswa")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="siswa_id", referencedColumnName="id", nullable=false)
     * })
     *
     * @var Siswa
     */
    private $siswa;

    /**
     * @Assert\File(maxSize="5M")
     *
     * @var UploadedFile
     */
    private $fileUpload;

    /**
     * @var string
     */
    private $fileDiskSebelumnya;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $lengkap
     */
    public function setLengkap($lengkap = false)
    {
        $this->lengkap = $lengkap;
    }

    /**
     * @return boolean
     */
    public function isLengkap()
    {
        return $this->lengkap;
    }

    /**
     * @param string $namaFile
     */
    public function setNamaFile($namaFile)
    {
        $this->namaFile = $namaFile;
    }

    /**
     * @return string
     */
    public function getNamaFile()
    {
        return strlen($this->namaFile) > 20 ? '...' . substr($this->namaFile, -17) : $this->namaFile;
    }

    /**
     * @param string $namaFileDisk
     */
    public function setNamaFileDisk($namaFileDisk)
    {
        $this->namaFileDisk = $namaFileDisk;
    }

    /**
     * @return string
     */
    public function getNamaFileDisk()
    {
        return $this->namaFileDisk;
    }

    /**
     * @param JenisDokumenSiswa $jenisDokumenSiswa
     */
    public function setJenisDokumenSiswa(JenisDokumenSiswa $jenisDokumenSiswa = null)
    {
        $this->jenisDokumenSiswa = $jenisDokumenSiswa;
    }

    /**
     * @return JenisDokumenSiswa
     */
    public function getJenisDokumenSiswa()
    {
        return $this->jenisDokumenSiswa;
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

    /**
     * @return UploadedFile
     */
    public function getFileUpload()
    {
        return $this->fileUpload;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFileUpload(UploadedFile $file)
    {
        $this->fileUpload = $file;
    }

    /**
     * @return null|string
     */
    public function getWebPathNamaFileDisk()
    {
        return (null === $this->namaFileDisk) ? null : $this->getUploadDir() . DIRECTORY_SEPARATOR . $this->namaFileDisk;
    }

    /**
     * @return null|string
     */
    public function getRelativePathNamaFileDisk()
    {
        return (null === $this->namaFileDisk) ? null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->namaFileDisk;
    }

    /**
     * @return null|string
     */
    public function getRelativePathNamaFileDiskSebelumnya()
    {
        return (null === $this->fileDiskSebelumnya) ? null : $this->getUploadRootDir() . DIRECTORY_SEPARATOR . $this->fileDiskSebelumnya;
    }

    /**
     * @param  string $type
     * @return string
     */
    public function getFilesizeNamaFileDisk($type = 'KB')
    {
        $file = new File($this->getRelativePathNamaFileDisk());

        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist()
    {
        if (null !== $this->fileUpload) {
            $this->fileDiskSebelumnya = $this->namaFileDisk;

            $this->namaFileDisk = sha1(uniqid(mt_rand(), true)) . '_' . $this->fileUpload->getClientOriginalName();
            $this->namaFile = $this->fileUpload->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postPersist()
    {
        if ($this->fileUpload === null) {
            return;
        }

        $this->fileUpload->move($this->getUploadRootDir(), $this->namaFileDisk);

        $this->removeFileSebelumnya();

        unset($this->fileUpload);
    }

    /**
     * Menghapus file yang tersimpan sebelumnya
     */
    private function removeFileSebelumnya()
    {
        if ($file = $this->getRelativePathNamaFileDiskSebelumnya()) {
            @unlink($file);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeFile()
    {
        if ($file = $this->getRelativePathNamaFileDisk()) {
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
                self::DOKUMEN_DIR
                . $this->getSiswa()->getSekolah()->getId()
                . DIRECTORY_SEPARATOR
                . $this->getSiswa()->getTahun()->getTahun())
        ) {
            $fs->mkdir(
                self::DOKUMEN_DIR
                . $this->getSiswa()->getSekolah()->getId()
                . DIRECTORY_SEPARATOR
                . $this->getSiswa()->getTahun()->getTahun()
            );
        }

        return self::DOKUMEN_DIR
            . $this->getSiswa()->getSekolah()->getId()
            . DIRECTORY_SEPARATOR
            . $this->getSiswa()->getTahun()->getTahun();
    }
}
