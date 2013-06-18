<?php

namespace Fast\SisdikBundle\Entity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Fast\SisdikBundle\Util\FileSizeFormatter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Sekolah
 *
 * @ORM\Table(name="sekolah")
 * @ORM\Entity
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
     * @ORM\Column(name="nama", type="string", length=300, nullable=false)
     */
    private $nama;

    /**
     * @var string
     *
     * @ORM\Column(name="kode", type="string", length=50, nullable=false)
     */
    private $kode;

    /**
     * @var string
     *
     * @ORM\Column(name="alamat", type="string", length=500, nullable=true)
     */
    private $alamat;

    /**
     * @var string
     *
     * @ORM\Column(name="kodepos", type="string", length=10, nullable=true)
     */
    private $kodepos;

    /**
     * @var string
     *
     * @ORM\Column(name="telepon", type="string", length=50, nullable=true)
     */
    private $telepon;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=50, nullable=true)
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="norekening", type="string", length=100, nullable=true)
     */
    private $norekening;

    /**
     * @var string
     *
     * @ORM\Column(name="bank", type="string", length=100, nullable=true)
     */
    private $bank;

    /**
     * @var string
     *
     * @ORM\Column(name="kepsek", type="string", length=400, nullable=false)
     */
    private $kepsek;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", type="string", length=200, nullable=true)
     */
    private $logo;

    /**
     * @var string
     *
     * @ORM\Column(name="logo_disk", type="string", length=200, nullable=true)
     */
    private $logoDisk;

    /**
     * @var UploadedFile
     *
     * @Assert\File(maxSize="300k")
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
     * Set nama
     *
     * @param string $nama
     * @return Sekolah
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
     * Set kode
     *
     * @param string $kode
     * @return Sekolah
     */
    public function setKode($kode) {
        $this->kode = $kode;

        return $this;
    }

    /**
     * Get kode
     *
     * @return string
     */
    public function getKode() {
        return $this->kode;
    }

    /**
     * Set alamat
     *
     * @param string $alamat
     * @return Sekolah
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
     * @return Sekolah
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
     * @return Sekolah
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
     * Set fax
     *
     * @param string $fax
     * @return Sekolah
     */
    public function setFax($fax) {
        $this->fax = $fax;

        return $this;
    }

    /**
     * Get fax
     *
     * @return string
     */
    public function getFax() {
        return $this->fax;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Sekolah
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
     * Set norekening
     *
     * @param string $norekening
     * @return Sekolah
     */
    public function setNorekening($norekening) {
        $this->norekening = $norekening;

        return $this;
    }

    /**
     * Get norekening
     *
     * @return string
     */
    public function getNorekening() {
        return $this->norekening;
    }

    /**
     * Set bank
     *
     * @param string $bank
     * @return Sekolah
     */
    public function setBank($bank) {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return string
     */
    public function getBank() {
        return $this->bank;
    }

    /**
     * Set kepsek
     *
     * @param string $kepsek
     * @return Sekolah
     */
    public function setKepsek($kepsek) {
        $this->kepsek = $kepsek;

        return $this;
    }

    /**
     * Get kepsek
     *
     * @return string
     */
    public function getKepsek() {
        return $this->kepsek;
    }

    /**
     * Set logo
     *
     * @param string $logo
     * @return Sekolah
     */
    public function setLogo($logo) {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo() {
        return strlen($this->logo) > 20 ? '...' . substr($this->logo, -17) : $this->logo;
    }

    /**
     * Set logoDisk
     *
     * @param string $logoDisk
     * @return Sekolah
     */
    public function setLogoDisk($logoDisk) {
        $this->logoDisk = $logoDisk;

        return $this;
    }

    /**
     * Get logoDisk
     *
     * @return string
     */
    public function getLogoDisk() {
        return $this->logoDisk;
    }

    public function getFileUpload() {
        return $this->fileUpload;
    }

    public function setFileUpload(UploadedFile $file) {
        $this->fileUpload = $file;

        return $this;
    }

    public function getWebPathLogoDisk() {
        return null === $this->logoDisk ? null : $this->getUploadDir() . '/' . $this->logoDisk;
    }

    public function getWebPathLogoThumbnailDisk() {
        return null === $this->logoDisk ? null
                : $this->getUploadDir() . '/' . self::THUMBNAIL_PREFIX . $this->logoDisk;
    }

    public function getRelativePathLogoDisk() {
        return null === $this->logoDisk ? null : $this->getUploadRootDir() . '/' . $this->logoDisk;
    }

    public function getRelativePathLogoDiskSebelumnya() {
        return null === $this->fileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . $this->fileDiskSebelumnya;
    }

    public function getRelativePathLogoThumbDiskSebelumnya() {
        return null === $this->fileDiskSebelumnya ? null
                : $this->getUploadRootDir() . '/' . self::THUMBNAIL_PREFIX . $this->fileDiskSebelumnya;
    }

    public function getFilesizeLogoDisk($type = 'KB') {
        $file = new File($this->getRelativePathLogoDisk());
        return FileSizeFormatter::formatBytes($file->getSize(), $type);
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function prePersist() {
        if (null !== $this->fileUpload) {
            $this->fileDiskSebelumnya = $this->logoDisk;

            $this->logoDisk = sha1(uniqid(mt_rand(), true)) . '_'
                    . $this->fileUpload->getClientOriginalName();
            $this->logo = $this->fileUpload->getClientOriginalName();
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

        if ($this->fileUpload->move($this->getUploadRootDir(), $this->logoDisk)) {

            $targetfile = $this->getRelativePathLogoDisk();
            $thumbnailfile = $this->getUploadRootDir() . '/' . self::THUMBNAIL_PREFIX . $this->logoDisk;

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
                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth,
                                    $resultHeight, $origWidth, $origHeight);

                            imagejpeg($resultImage, $thumbnailfile, 90);
                        }
                        break;
                    case IMAGETYPE_PNG:
                        if (imagetypes() & IMG_PNG) {
                            // resample image
                            // for png, we use imagecreate instead
                            $resultImage = imagecreate($resultWidth, $resultHeight);

                            $srcImage = imagecreatefrompng($targetfile);
                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth,
                                    $resultHeight, $origWidth, $origHeight);

                            imagepng($resultImage, $thumbnailfile, 0);
                        }
                        break;
                    case IMAGETYPE_GIF:
                        if (imagetypes() & IMG_GIF) {
                            $resultImage = imagecreatetruecolor($resultWidth, $resultHeight);

                            $srcImage = imagecreatefromgif($targetfile);
                            imagecopyresampled($resultImage, $srcImage, 0, 0, 0, 0, $resultWidth,
                                    $resultHeight, $origWidth, $origHeight);

                            imagegif($resultImage, $thumbnailfile);
                        }
                        break;
                }
            }
        }

        $this->removeFileSebelumnya();

        unset($this->fileUpload);
    }

    private function removeFileSebelumnya() {
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
    public function removeFile() {
        if ($file = $this->getRelativePathLogoDisk()) {
            @unlink($file);
        }
    }

    protected function getUploadRootDir() {
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        $fs = new Filesystem();
        if (!$fs->exists(self::LOGO_DIR . $this->getId())) {
            $fs->mkdir(self::LOGO_DIR . $this->getId());
        }
        return self::LOGO_DIR . $this->getId();
    }
}
