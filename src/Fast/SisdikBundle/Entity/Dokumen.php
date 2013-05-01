<?php

namespace Fast\SisdikBundle\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class Dokumen
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $lengkap;

    /**
     * @var string
     */
    private $namaFile;

    /**
     * @var string
     */
    private $namaFileDisk;

    /**
     * @var \JenisDokumenSiswa
     */
    private $jenisDokumenSiswa;

    /**
     * @var \Siswa
     */
    private $siswa;

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="5000000")
     */
    private $fileUpload;

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
     * @return Dokumen
     */
    public function setLengkap($lengkap) {
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
     * @return Dokumen
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
        return $this->namaFile;
    }

    /**
     * Set namaFileDisk
     *
     * @param string $namaFileDisk
     * @return Dokumen
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
     * @return Dokumen
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
     * @return Dokumen
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
}
