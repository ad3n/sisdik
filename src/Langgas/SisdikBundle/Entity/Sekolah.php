<?php

namespace Langgas\SisdikBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Langgas\SisdikBundle\Util\FileSizeFormatter;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Table(name="sekolah")
 * @ORM\Entity(repositoryClass="Langgas\SisdikBundle\Repository\SekolahRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Sekolah
{
    const LOGO_DIR = 'uploads/sekolah/logo/';
    const THUMBNAIL_PREFIX = 'th1-';
    const MEMORY_LIMIT = '256M';
    const PHOTO_THUMB_WIDTH = 80;
    const PHOTO_THUMB_HEIGHT = 80;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="nama", type="string", length=300, nullable=false)
     *
     * @var string
     */
    private $nama;

    /**
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     *
     * @var string
     */
    private $kode;

    /**
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     *
     * @var string
     */
    private $alamat;

    /**
     * @ORM\Column(name="kodepos", type="string", length=10, nullable=true)
     *
     * @var string
     */
    private $kodepos;

    /**
     * @ORM\Column(name="telepon", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $telepon;

    /**
     * @ORM\Column(name="fax", type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $fax;

    /**
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="norekening", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $norekening;

    /**
     * @ORM\Column(name="bank", type="string", length=100, nullable=true)
     *
     * @var string
     */
    private $bank;

    /**
     * @ORM\Column(name="kepsek", type="string", length=400, nullable=false)
     *
     * @var string
     */
    private $kepsek;

    /**
     * @ORM\Column(name="logo", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $logo;

    /**
     * @ORM\Column(name="logo_disk", type="string", length=200, nullable=true)
     *
     * @var string
     */
    private $logoDisk;

    /**
     * @ORM\Column(name="awal_pembiayaan", type="string", length=5, nullable=false, options={"fixed"=true, "default"="01/07"})
     * @Assert\NotBlank
     * @Assert\Length(min=5, max=5)
     *
     * @var string
     */
    private $awalPembiayaan = '01/07';

    /**
     * @ORM\Column(name="akhir_pembiayaan", type="string", length=5, nullable=false, options={"fixed"=true, "default"="30/06"})
     * @Assert\NotBlank
     * @Assert\Length(min=5, max=5)
     *
     * @var string
     */
    private $akhirPembiayaan = '30/06';

    /**
     * @Assert\File(maxSize="300k")
     *
     * @var UploadedFile
     */
    private $fileUpload;

    /**
     * @var string
     */
    private $fileDiskSebelumnya;

    /**
     * @ORM\Column(name="nomor_urut", type="integer", nullable=true, options={"unsigned"=true})
     *
     * @var integer
     */
    private $nomorUrut;

    /**
     * @ORM\Column(name="atr_no_daftar", type="string", length=200, nullable=true)
     *
     * @var integer
     */
    private $atributNomorDaftar;

    /**
     * @ORM\Column(name="atr_no_trans_biaya_daftar", type="string", length=200, nullable=true)
     *
     * @var integer
     */
    private $atributNomorTransaksiBiayaDaftar;

    /**
     * @ORM\Column(name="atr_no_trans_biaya_sekali", type="string", length=200, nullable=true)
     *
     * @var integer
     */
    private $atributNomorTransaksiBiayaSekali;

    /**
     * @ORM\Column(name="atr_no_trans_biaya_berulang", type="string", length=200, nullable=true)
     *
     * @var integer
     */
    private $atributNomorTransaksiBiayaBerulang;

    /**
     * @ORM\Column(name="atr_no_trans_restitusi", type="string", length=200, nullable=true)
     *
     * @var integer
     */
    private $atributNomorTransaksiRestitusi;

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
     * @param string $kode
     */
    public function setKode($kode)
    {
        $this->kode = $kode;
    }

    /**
     * @return string
     */
    public function getKode()
    {
        return $this->kode;
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
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
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
     * @param string $norekening
     */
    public function setNorekening($norekening)
    {
        $this->norekening = $norekening;
    }

    /**
     * @return string
     */
    public function getNorekening()
    {
        return $this->norekening;
    }

    /**
     * @param string $bank
     */
    public function setBank($bank)
    {
        $this->bank = $bank;
    }

    /**
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * @param string $kepsek
     */
    public function setKepsek($kepsek)
    {
        $this->kepsek = $kepsek;
    }

    /**
     * @return string
     */
    public function getKepsek()
    {
        return $this->kepsek;
    }

    /**
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return strlen($this->logo) > 20 ? '...'.substr($this->logo, -17) : $this->logo;
    }

    /**
     * @param string $logoDisk
     */
    public function setLogoDisk($logoDisk)
    {
        $this->logoDisk = $logoDisk;
    }

    /**
     * @return string
     */
    public function getLogoDisk()
    {
        return $this->logoDisk;
    }

    /**
     * @param string $awalPembiayaan
     */
    public function setAwalPembiayaan($awalPembiayaan)
    {
        $this->awalPembiayaan = $awalPembiayaan;
    }

    /**
     * @return string
     */
    public function getAwalPembiayaan()
    {
        return $this->awalPembiayaan;
    }

    /**
     * @param string $akhirPembiayaan
     */
    public function setAkhirPembiayaan($akhirPembiayaan)
    {
        $this->akhirPembiayaan = $akhirPembiayaan;
    }

    /**
     * @return string
     */
    public function getAkhirPembiayaan()
    {
        return $this->akhirPembiayaan;
    }

    /**
     * @param integer $nomorUrut
     */
    public function setNomorUrut($nomorUrut)
    {
        $this->nomorUrut = $nomorUrut;
    }

    /**
     * @return integer
     */
    public function getNomorUrut()
    {
        return $this->nomorUrut;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFileUpload(UploadedFile $file)
    {
        $this->fileUpload = $file;
    }

    /**
     * @return UploadedFile
     */
    public function getFileUpload()
    {
        return $this->fileUpload;
    }

    /**
     * @return NULL|string
     */
    public function getWebPathLogoDisk()
    {
        return null === $this->logoDisk ? null : $this->getUploadDir().DIRECTORY_SEPARATOR.$this->logoDisk;
    }

    /**
     * @return NULL|string
     */
    public function getWebPathLogoThumbnailDisk()
    {
        return null === $this->logoDisk ?
            null : $this->getUploadDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->logoDisk
        ;
    }

    /**
     * @return NULL|string
     */
    public function getRelativePathLogoDisk()
    {
        return null === $this->logoDisk ?
            null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->logoDisk
        ;
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getRelativePathLogoDiskSebelumnya()
    {
        return null === $this->fileDiskSebelumnya ?
            null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->fileDiskSebelumnya
        ;
    }

    /**
     * @return Ambigous NULL|string
     */
    public function getRelativePathLogoThumbDiskSebelumnya()
    {
        return null === $this->fileDiskSebelumnya ?
            null : $this->getUploadRootDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->fileDiskSebelumnya
        ;
    }

    /**
     * @param  string $type
     * @return string
     */
    public function getFilesizeLogoDisk($type = 'KB')
    {
        $file = new File($this->getRelativePathLogoDisk());

        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist()
    {
        if (null !== $this->fileUpload) {
            $this->fileDiskSebelumnya = $this->logoDisk;

            $this->logoDisk = sha1(uniqid(mt_rand(), true)).'_'.$this->fileUpload->getClientOriginalName();

            $this->logo = $this->fileUpload->getClientOriginalName();
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

        if ($this->fileUpload->move($this->getUploadRootDir(), $this->logoDisk)) {
            $targetfile = $this->getRelativePathLogoDisk();
            $thumbnailfile = $this->getUploadRootDir().DIRECTORY_SEPARATOR.self::THUMBNAIL_PREFIX.$this->logoDisk;

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
                            $resultImage = imagecreatetruecolor($resultWidth, $resultHeight);

                            $srcImage = imagecreatefrompng($targetfile);
                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth, $resultHeight, $origWidth, $origHeight);

                            imagepng($resultImage, $thumbnailfile, 0);
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

        $this->removeFileSebelumnya();

        unset($this->fileUpload);
    }

    private function removeFileSebelumnya()
    {
        if ($file = $this->getRelativePathLogoDiskSebelumnya()) {
            @unlink($file);
        }
        if ($fileThumb = $this->getRelativePathLogoThumbDiskSebelumnya()) {
            @unlink($fileThumb);
        }
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeFile()
    {
        if ($file = $this->getRelativePathLogoDisk()) {
            @unlink($file);
        }
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
        if (!$fs->exists(self::LOGO_DIR.$this->getId())) {
            $fs->mkdir(self::LOGO_DIR.$this->getId());
        }

        return self::LOGO_DIR.$this->getId();
    }

    /**
     * @param string $atributNomorDaftar
     */
    public function setAtributNomorDaftar($atributNomorDaftar)
    {
        $this->atributNomorDaftar = $atributNomorDaftar;
    }

    /**
     * @return string
     */
    public function getAtributNomorDaftar()
    {
        return $this->atributNomorDaftar;
    }

    /**
     * @param string $atributNomorTransaksiBiayaDaftar
     */
    public function setAtributNomorTransaksiBiayaDaftar($atributNomorTransaksiBiayaDaftar)
    {
        $this->atributNomorTransaksiBiayaDaftar = $atributNomorTransaksiBiayaDaftar;
    }

    /**
     * @return string
     */
    public function getAtributNomorTransaksiBiayaDaftar()
    {
        return $this->atributNomorTransaksiBiayaDaftar;
    }

    /**
     * @param string $atributNomorTransaksiBiayaSekali
     */
    public function setAtributNomorTransaksiBiayaSekali($atributNomorTransaksiBiayaSekali)
    {
        $this->atributNomorTransaksiBiayaSekali = $atributNomorTransaksiBiayaSekali;
    }

    /**
     * @return string
     */
    public function getAtributNomorTransaksiBiayaSekali()
    {
        return $this->atributNomorTransaksiBiayaSekali;
    }

    /**
     * @param string $atributNomorTransaksiBiayaBerulang
     */
    public function setAtributNomorTransaksiBiayaBerulang($atributNomorTransaksiBiayaBerulang)
    {
        $this->atributNomorTransaksiBiayaBerulang = $atributNomorTransaksiBiayaBerulang;
    }

    /**
     * @return string
     */
    public function getAtributNomorTransaksiBiayaBerulang()
    {
        return $this->atributNomorTransaksiBiayaBerulang;
    }

    /**
     * @param string $atributNomorTransaksiRestitusi
     */
    public function setAtributNomorTransaksiRestitusi($atributNomorTransaksiRestitusi)
    {
        $this->atributNomorTransaksiRestitusi = $atributNomorTransaksiRestitusi;
    }

    /**
     * @return string
     */
    public function getAtributNomorTransaksiRestitusi()
    {
        return $this->atributNomorTransaksiRestitusi;
    }
}
