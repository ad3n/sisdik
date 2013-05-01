<?php

namespace Fast\SisdikBundle\Entity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * DokumenSiswa
 *
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
     * @ORM\Column(name="lengkap", type="boolean", nullable=false, options={"default"=0})
     */
    private $lengkap = false;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_file", type="string", length=255, nullable=true)
     */
    private $namaFile;

    /**
     * @var string
     *
     * @ORM\Column(name="nama_file_disk", type="string", length=255, nullable=true)
     */
    private $namaFileDisk;

    /**
     * @var \JenisDokumenSiswa
     *
     * @ORM\ManyToOne(targetEntity="JenisDokumenSiswa")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="jenis_dokumen_siswa_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $jenisDokumenSiswa;

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
     * @var UploadedFile
     *
     * @Assert\File(maxSize="5M")
     */
    private $fileUpload;

    /**
     * @var string
     */
    private $fileDiskSebelumnya;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set lengkap
     *
     * @param boolean $lengkap
     * @return DokumenSiswa
     */
    public function setLengkap($lengkap = false) {
        $this->lengkap = $lengkap;

        return $this;
    }

    /**
     * Get lengkap
     *
     * @return boolean
     */
    public function isLengkap() {
        return $this->lengkap;
    }

    /**
     * Set namaFile
     *
     * @param string $namaFile
     * @return DokumenSiswa
     */
    public function setNamaFile($namaFile) {
        $this->namaFile = $namaFile;

        return $this;
    }

    /**
     * Get namaFile
     *
     * @return string
     */
    public function getNamaFile() {
        return strlen($this->namaFile) > 20 ? '...' . substr($this->namaFile, -17) : $this->namaFile;
    }

    /**
     * Set namaFileDisk
     *
     * @param string $namaFileDisk
     * @return DokumenSiswa
     */
    public function setNamaFileDisk($namaFileDisk) {
        $this->namaFileDisk = $namaFileDisk;

        return $this;
    }

    /**
     * Get namaFileDisk
     *
     * @return string
     */
    public function getNamaFileDisk() {
        return $this->namaFileDisk;
    }

    /**
     * Set jenisDokumenSiswa
     *
     * @param \Fast\SisdikBundle\Entity\JenisDokumenSiswa $jenisDokumenSiswa
     * @return DokumenSiswa
     */
    public function setJenisDokumenSiswa(
            \Fast\SisdikBundle\Entity\JenisDokumenSiswa $jenisDokumenSiswa = null) {
        $this->jenisDokumenSiswa = $jenisDokumenSiswa;

        return $this;
    }

    /**
     * Get jenisDokumenSiswa
     *
     * @return \Fast\SisdikBundle\Entity\JenisDokumenSiswa
     */
    public function getJenisDokumenSiswa() {
        return $this->jenisDokumenSiswa;
    }

    /**
     * Set siswa
     *
     * @param \Fast\SisdikBundle\Entity\Siswa $siswa
     * @return DokumenSiswa
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

    public function getFileUpload() {
        return $this->fileUpload;
    }

    public function setFileUpload(UploadedFile $file) {
        $this->fileUpload = $file;

        return $this;
    }

    public function getWebPathNamaFileDisk() {
        return null === $this->namaFileDisk ? null : $this->getUploadDir() . '/' . $this->namaFileDisk;
    }

    public function getRelativePathNamaFileDisk() {
        return null === $this->namaFileDisk ? null : $this->getUploadRootDir() . '/' . $this->namaFileDisk;
    }

    public function getRelativePathNamaFileDiskSebelumnya() {
        return null === $this->fileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . $this->fileDiskSebelumnya;
    }

    public function getFilesizeNamaFileDisk($type = 'KB') {
        $file = new File($this->getRelativePathNamaFileDisk());
        return $this->formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist() {
        if (null !== $this->fileUpload) {
            $this->fileDiskSebelumnya = $this->namaFileDisk;

            $this->namaFileDisk = sha1(uniqid(mt_rand(), true)) . '_'
                    . $this->fileUpload->getClientOriginalName();
            $this->namaFile = $this->fileUpload->getClientOriginalName();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function postPersist() {
        if ($this->fileUpload === null) {
            return;
        }

        $this->fileUpload->move($this->getUploadRootDir(), $this->namaFileDisk);

        $this->removeFileSebelumnya();

        unset($this->fileUpload);
    }

    private function removeFileSebelumnya() {
        if ($file = $this->getRelativePathNamaFileDiskSebelumnya()) {
            unlink($file);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeFile() {
        if ($file = $this->getRelativePathNamaFileDisk()) {
            unlink($file);
        }
    }

    protected function getUploadRootDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        $fs = new Filesystem();
        if (!$fs->exists(self::DOKUMEN_DIR)) {
            $fs->mkdir(self::DOKUMEN_DIR);
        }
        if (!$fs->exists(self::DOKUMEN_DIR . $this->getSiswa()->getSekolah()->getId())) {
            $fs->mkdir(self::DOKUMEN_DIR . $this->getSiswa()->getSekolah()->getId());
        }
        if (!$fs
                ->exists(
                        self::DOKUMEN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                                . $this->getSiswa()->getTahun()->getTahun())) {
            $fs
                    ->mkdir(
                            self::DOKUMEN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                                    . $this->getSiswa()->getTahun()->getTahun());
        }
        return self::DOKUMEN_DIR . $this->getSiswa()->getSekolah()->getId() . '/'
                . $this->getSiswa()->getTahun()->getTahun();
    }

    private function formatBytes($size, $type) {
        switch ($type) {
            case "KB":
                $size = $size * .0009765625;
                break;
            case "MB":
                $size = ($size * .0009765625) * .0009765625;
                break;
            case "GB":
                $size = (($size * .0009765625) * .0009765625) * .0009765625;
                break;
        }
        if ($size <= 0) {
            return $size = 'unknown';
        } else {
            return round($size, 2) . ' ' . $type;
        }
    }
}
