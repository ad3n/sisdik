<?php

namespace Application\Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration, Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130304105749 extends AbstractMigration
{
    private $trigger1 = "CREATE TRIGGER `befins_calonpr`
BEFORE INSERT ON `calon_pembayaran_rutin`
FOR EACH ROW
BEGIN
    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $trigger2 = "CREATE TRIGGER `befins_calonps`
BEFORE INSERT ON `calon_pembayaran_sekali`
FOR EACH ROW
BEGIN
    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $trigger3 = "CREATE TRIGGER `befins_calonsiswa`
BEFORE INSERT ON `calon_siswa`
FOR EACH ROW
BEGIN
    DECLARE nomorurutpendaftaran INT;

    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();

    SET nomorurutpendaftaran = (SELECT MAX(nomor_urut_pendaftaran) FROM calon_siswa WHERE tahunmasuk_id = NEW.tahunmasuk_id);
    SET NEW.nomor_urut_pendaftaran = IFNULL(nomorurutpendaftaran, 0) + 1;
    SET NEW.nomor_pendaftaran =  CONCAT(CAST((SELECT tahun FROM tahunmasuk WHERE id = NEW.tahunmasuk_id) AS CHAR(4)), NEW.nomor_urut_pendaftaran);
END";

    private $trigger4 = "CREATE TRIGGER `beforeinsertkelas`
BEFORE INSERT ON `kelas`
FOR EACH ROW
BEGIN
    SET NEW.kode = CONCAT((SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id), NEW.kode);
END";

    private $trigger5 = "CREATE TRIGGER `beforeupdatekelas`
BEFORE UPDATE ON `kelas`
FOR EACH ROW
BEGIN
    DECLARE kodetahun VARCHAR(45);
    DECLARE kodekelas VARCHAR(50);

    SET kodetahun = (SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id);
    SET kodekelas = NEW.kode;
    SET NEW.kode = IF(LEFT(kodekelas, LENGTH(kodetahun)) = kodetahun, kodekelas, CONCAT(kodetahun, NEW.kode));
END";

    private $trigger6 = "CREATE TRIGGER `beforeinsertsiswa`
BEFORE INSERT ON `siswa`
FOR EACH ROW
BEGIN
    DECLARE nomorurutpersekolah INT;

    SET nomorurutpersekolah = (SELECT MAX(nomor_urut_persekolah) FROM siswa WHERE sekolah_id = NEW.sekolah_id);
    SET NEW.nomor_urut_persekolah = IFNULL(nomorurutpersekolah,100000) + 1;
    SET NEW.nomor_induk_sistem = CONCAT(CAST(NEW.nomor_urut_persekolah AS CHAR(6)), NEW.sekolah_id);
END";

    private $trigger7 = "CREATE TRIGGER `befup_calonsiswa`
BEFORE UPDATE ON `calon_siswa`
FOR EACH ROW
BEGIN
    SET NEW.waktu_ubah = NOW();
END";

    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this
                ->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql",
                        "Migration can only be executed safely on 'mysql'.");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `sekolah` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `nama` VARCHAR(300) NOT NULL ,
                            `kode` VARCHAR(50) NOT NULL ,
                            `alamat` VARCHAR(500) NULL DEFAULT NULL ,
                            `kodepos` VARCHAR(10) NULL DEFAULT NULL ,
                            `telepon` VARCHAR(50) NULL DEFAULT NULL ,
                            `fax` VARCHAR(50) NULL DEFAULT NULL ,
                            `email` VARCHAR(100) NOT NULL ,
                            `norekening` VARCHAR(100) NULL DEFAULT NULL ,
                            `bank` VARCHAR(100) NULL DEFAULT NULL ,
                            `kepsek` VARCHAR(400) NOT NULL ,
                            PRIMARY KEY (`id`) )
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `gelombang` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NOT NULL ,
                            `nama` VARCHAR(300) NULL DEFAULT NULL ,
                            `kode` VARCHAR(50) NULL DEFAULT NULL ,
                            `keterangan` VARCHAR(400) NULL DEFAULT NULL ,
                            `urutan` SMALLINT(6) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_gelombang_sekolah1_idx` (`sekolah_id` ASC) ,
                            CONSTRAINT `fk_gelombang_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `tahunmasuk` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NOT NULL ,
                            `tahun` YEAR NOT NULL ,
                            PRIMARY KEY (`id`) ,
                            UNIQUE INDEX `UNIQ_tahunmasuk1` (`sekolah_id` ASC, `tahun` ASC) ,
                            INDEX `fk_tahunmasuk_sekolah1_idx` (`sekolah_id` ASC) ,
                            CONSTRAINT `fk_tahunmasuk_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jenisbiaya` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NOT NULL ,
                            `nama` VARCHAR(300) NULL DEFAULT NULL ,
                            `kode` VARCHAR(50) NULL DEFAULT NULL ,
                            `keterangan` VARCHAR(400) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_jenisbiaya_sekolah1_idx` (`sekolah_id` ASC) ,
                            CONSTRAINT `fk_jenisbiaya_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `biaya_rutin` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `jenisbiaya_id` INT(11) NOT NULL ,
                            `tahunmasuk_id` INT(11) NOT NULL ,
                            `gelombang_id` INT(11) NOT NULL ,
                            `nominal` BIGINT(20) NULL DEFAULT NULL ,
                            `perulangan` ENUM('hari','minggu','bulan','tahun') NULL DEFAULT 'bulan' ,
                            `urutan` SMALLINT(6) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `IDX_F4EDECBC806A4F0C` (`jenisbiaya_id` ASC) ,
                            INDEX `IDX_F4EDECBC299E2F11` (`tahunmasuk_id` ASC) ,
                            INDEX `IDX_F4EDECBC1C9FFB46` (`gelombang_id` ASC) ,
                            UNIQUE INDEX `UNQ_biaya_rutin1` (`jenisbiaya_id` ASC, `tahunmasuk_id` ASC, `gelombang_id` ASC) ,
                            CONSTRAINT `FK_F4EDECBC1C9FFB46`
                                FOREIGN KEY (`gelombang_id` )
                                REFERENCES `gelombang` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT,
                            CONSTRAINT `FK_F4EDECBC299E2F11`
                                FOREIGN KEY (`tahunmasuk_id` )
                                REFERENCES `tahunmasuk` (`id` )
                                ON UPDATE RESTRICT,
                            CONSTRAINT `FK_F4EDECBC806A4F0C`
                                FOREIGN KEY (`jenisbiaya_id` )
                                REFERENCES `jenisbiaya` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `biaya_sekali` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `jenisbiaya_id` INT(11) NOT NULL ,
                            `tahunmasuk_id` INT(11) NOT NULL ,
                            `gelombang_id` INT(11) NOT NULL ,
                            `nominal` BIGINT(20) NULL DEFAULT NULL ,
                            `urutan` SMALLINT(6) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `IDX_BBAC14EE806A4F0C` (`jenisbiaya_id` ASC) ,
                            INDEX `IDX_BBAC14EE299E2F11` (`tahunmasuk_id` ASC) ,
                            INDEX `IDX_BBAC14EE1C9FFB46` (`gelombang_id` ASC) ,
                            UNIQUE INDEX `UNQ_biaya_sekali1` (`jenisbiaya_id` ASC, `tahunmasuk_id` ASC, `gelombang_id` ASC) ,
                            CONSTRAINT `FK_BBAC14EE1C9FFB46`
                                FOREIGN KEY (`gelombang_id` )
                                REFERENCES `gelombang` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT,
                            CONSTRAINT `FK_BBAC14EE299E2F11`
                                FOREIGN KEY (`tahunmasuk_id` )
                                REFERENCES `tahunmasuk` (`id` )
                                ON UPDATE RESTRICT,
                            CONSTRAINT `FK_BBAC14EE806A4F0C`
                                FOREIGN KEY (`jenisbiaya_id` )
                                REFERENCES `jenisbiaya` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `guru` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NOT NULL ,
                            `nama` VARCHAR(400) NULL DEFAULT NULL ,
                            `jenis_kelamin` VARCHAR(255) NULL DEFAULT NULL ,
                            `foto` VARCHAR(400) NULL DEFAULT NULL ,
                            `agama` INT(11) NULL DEFAULT NULL ,
                            `tempat_lahir` VARCHAR(400) NULL DEFAULT NULL ,
                            `tanggal_lahir` DATE NULL DEFAULT NULL ,
                            `alamat` VARCHAR(500) NULL DEFAULT NULL ,
                            `telepon` VARCHAR(100) NULL DEFAULT NULL ,
                            `email` VARCHAR(100) NULL DEFAULT NULL ,
                            `nomor_induk` VARCHAR(50) NULL DEFAULT NULL ,
                            `username` VARCHAR(255) NULL DEFAULT NULL ,
                            `status` TINYINT(1) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_guru_sekolah1_idx` (`sekolah_id` ASC) ,
                            CONSTRAINT `fk_guru_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `tahunmasuk_id` INT(11) NOT NULL ,
  `gelombang_id` INT(11) NOT NULL ,
  `nomor_urut_persekolah` MEDIUMINT(6) UNSIGNED NULL DEFAULT NULL ,
  `nomor_induk_sistem` VARCHAR(45) NULL DEFAULT NULL ,
  `nomor_pendaftaran` VARCHAR(45) NULL DEFAULT NULL ,
  `nomor_induk` VARCHAR(100) NULL DEFAULT NULL ,
  `nama_lengkap` VARCHAR(300) NULL DEFAULT NULL ,
  `jenis_kelamin` VARCHAR(100) NULL DEFAULT NULL ,
  `foto_pendaftaran` VARCHAR(100) NULL DEFAULT NULL ,
  `foto` VARCHAR(100) NULL DEFAULT NULL ,
  `agama` VARCHAR(100) NULL DEFAULT NULL ,
  `tempat_lahir` VARCHAR(400) NULL DEFAULT NULL ,
  `tanggal_lahir` DATE NULL DEFAULT NULL ,
  `email` VARCHAR(100) NULL DEFAULT NULL ,
  `nama_panggilan` VARCHAR(100) NULL DEFAULT NULL ,
  `kewarganegaraan` VARCHAR(200) NULL DEFAULT NULL ,
  `anak_ke` SMALLINT NULL DEFAULT NULL ,
  `jumlah_saudarakandung` SMALLINT NULL DEFAULT NULL ,
  `jumlah_saudaratiri` SMALLINT NULL DEFAULT NULL ,
  `status_orphan` VARCHAR(100) NULL DEFAULT NULL ,
  `bahasa_seharihari` VARCHAR(200) NULL DEFAULT NULL ,
  `alamat` VARCHAR(500) NULL DEFAULT NULL ,
  `kodepos` VARCHAR(30) NULL DEFAULT NULL ,
  `telepon` VARCHAR(100) NULL DEFAULT NULL ,
  `ponsel_siswa` VARCHAR(100) NULL DEFAULT NULL ,
  `ponsel_orangtuawali` VARCHAR(100) NULL DEFAULT NULL ,
  `sekolah_tinggaldi` VARCHAR(400) NULL DEFAULT NULL ,
  `jarak_tempat` VARCHAR(300) NULL DEFAULT NULL ,
  `cara_kesekolah` VARCHAR(300) NULL DEFAULT NULL ,
  `beratbadan` SMALLINT NULL DEFAULT NULL ,
  `tinggibadan` SMALLINT NULL DEFAULT NULL ,
  `golongandarah` VARCHAR(50) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `UNIQ_siswa1` (`sekolah_id` ASC, `nomor_urut_persekolah` ASC) ,
  UNIQUE INDEX `nomor_induk_sistem_UNIQUE` (`nomor_induk_sistem` ASC) ,
  INDEX `IDX_3202BD7D299E2F11` (`tahunmasuk_id` ASC) ,
  INDEX `IDX_3202BD7D1C9FFB46` (`gelombang_id` ASC) ,
  INDEX `fk_siswa_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `FK_3202BD7D1C9FFB46`
    FOREIGN KEY (`gelombang_id` )
    REFERENCES `gelombang` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_3202BD7D299E2F11`
    FOREIGN KEY (`tahunmasuk_id` )
    REFERENCES `tahunmasuk` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_siswa_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `staf` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NOT NULL ,
                            `nama_lengkap` VARCHAR(300) NULL DEFAULT NULL ,
                            `username` VARCHAR(255) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_staf_sekolah1_idx` (`sekolah_id` ASC) ,
                            CONSTRAINT `fk_staf_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `fos_user` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `sekolah_id` INT(11) NULL DEFAULT NULL ,
                            `siswa_id` INT(11) NULL DEFAULT NULL ,
                            `guru_id` INT(11) NULL DEFAULT NULL ,
                            `staf_id` INT(11) NULL DEFAULT NULL ,
                            `username` VARCHAR(255) NOT NULL ,
                            `username_canonical` VARCHAR(255) NOT NULL ,
                            `email` VARCHAR(255) NOT NULL ,
                            `email_canonical` VARCHAR(255) NOT NULL ,
                            `enabled` TINYINT(1) NOT NULL ,
                            `salt` VARCHAR(255) NOT NULL ,
                            `password` VARCHAR(255) NOT NULL ,
                            `last_login` DATETIME NULL DEFAULT NULL ,
                            `locked` TINYINT(1) NOT NULL ,
                            `expired` TINYINT(1) NOT NULL ,
                            `expires_at` DATETIME NULL DEFAULT NULL ,
                            `confirmation_token` VARCHAR(255) NULL DEFAULT NULL ,
                            `password_requested_at` DATETIME NULL DEFAULT NULL ,
                            `roles` LONGTEXT NOT NULL COMMENT '(DC2Type:array)' ,
                            `credentials_expired` TINYINT(1) NOT NULL ,
                            `credentials_expire_at` DATETIME NULL DEFAULT NULL ,
                            `name` VARCHAR(255) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) ,
                            UNIQUE INDEX `UNIQ_957A647992FC23A8` (`username_canonical` ASC) ,
                            UNIQUE INDEX `UNIQ_957A6479A0D96FBF` (`email_canonical` ASC) ,
                            INDEX `fk_fos_user_sekolah1_idx` (`sekolah_id` ASC) ,
                            INDEX `fk_fos_user_siswa1_idx` (`siswa_id` ASC) ,
                            INDEX `fk_fos_user_guru1_idx` (`guru_id` ASC) ,
                            INDEX `fk_fos_user_staf1_idx` (`staf_id` ASC) ,
                            CONSTRAINT `fk_fos_user_guru1`
                                FOREIGN KEY (`guru_id` )
                                REFERENCES `guru` (`id` )
                                ON DELETE NO ACTION
                                ON UPDATE NO ACTION,
                            CONSTRAINT `fk_fos_user_sekolah1`
                                FOREIGN KEY (`sekolah_id` )
                                REFERENCES `sekolah` (`id` )
                                ON DELETE NO ACTION
                                ON UPDATE NO ACTION,
                            CONSTRAINT `fk_fos_user_siswa1`
                                FOREIGN KEY (`siswa_id` )
                                REFERENCES `siswa` (`id` )
                                ON DELETE NO ACTION
                                ON UPDATE NO ACTION,
                            CONSTRAINT `fk_fos_user_staf1`
                                FOREIGN KEY (`staf_id` )
                                REFERENCES `staf` (`id` )
                                ON DELETE NO ACTION
                                ON UPDATE NO ACTION)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `referensi` (
                            `id` INT(11) NOT NULL AUTO_INCREMENT ,
                            `nama` VARCHAR(400) NULL DEFAULT NULL ,
                            `ponsel` VARCHAR(50) NULL DEFAULT NULL ,
                            `alamat` VARCHAR(500) NULL DEFAULT NULL ,
                            `nomor_identitas` VARCHAR(300) NULL DEFAULT NULL ,
                            PRIMARY KEY (`id`) )
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `calon_siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `tahunmasuk_id` INT(11) NOT NULL ,
  `gelombang_id` INT(11) NOT NULL ,
  `referensi_id` INT(11) NULL ,
  `dibuat_oleh_id` INT(11) NOT NULL ,
  `diubah_oleh_id` INT(11) NULL ,
  `nomor_urut_pendaftaran` SMALLINT(3) UNSIGNED ZEROFILL NULL DEFAULT NULL ,
  `nomor_pendaftaran` VARCHAR(45) NULL DEFAULT NULL ,
  `nama_lengkap` VARCHAR(300) NULL DEFAULT NULL ,
  `jenis_kelamin` VARCHAR(100) NULL DEFAULT NULL ,
  `foto_pendaftaran` VARCHAR(100) NULL DEFAULT NULL ,
  `foto` VARCHAR(100) NULL DEFAULT NULL ,
  `agama` VARCHAR(100) NULL ,
  `tempat_lahir` VARCHAR(400) NULL DEFAULT NULL ,
  `tanggal_lahir` DATE NULL DEFAULT NULL ,
  `email` VARCHAR(100) NULL ,
  `nama_panggilan` VARCHAR(100) NULL ,
  `kewarganegaraan` VARCHAR(200) NULL ,
  `anak_ke` SMALLINT NULL ,
  `jumlah_saudarakandung` SMALLINT NULL ,
  `jumlah_saudaratiri` SMALLINT NULL ,
  `status_orphan` VARCHAR(100) NULL ,
  `bahasa_seharihari` VARCHAR(200) NULL ,
  `alamat` VARCHAR(500) NULL DEFAULT NULL ,
  `kodepos` VARCHAR(30) NULL ,
  `telepon` VARCHAR(100) NULL ,
  `ponsel_siswa` VARCHAR(100) NULL ,
  `ponsel_orangtuawali` VARCHAR(100) NULL ,
  `sekolah_tinggaldi` VARCHAR(400) NULL ,
  `jarak_tempat` VARCHAR(300) NULL ,
  `cara_kesekolah` VARCHAR(300) NULL ,
  `beratbadan` SMALLINT NULL ,
  `tinggibadan` SMALLINT NULL ,
  `golongandarah` VARCHAR(50) NULL ,
  `waktu_simpan` DATETIME NULL ,
  `waktu_ubah` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_calon_siswa_fos_user1_idx` (`dibuat_oleh_id` ASC) ,
  INDEX `fk_calon_siswa_sekolah1_idx` (`sekolah_id` ASC) ,
  INDEX `fk_calon_siswa_tahunmasuk1_idx` (`tahunmasuk_id` ASC) ,
  INDEX `fk_calon_siswa_gelombang1_idx` (`gelombang_id` ASC) ,
  INDEX `fk_calon_siswa_fos_user2_idx` (`diubah_oleh_id` ASC) ,
  INDEX `fk_calon_siswa_referensi1_idx` (`referensi_id` ASC) ,
  UNIQUE INDEX `nomor_pendaftaran_UNIQUE` (`nomor_pendaftaran` ASC) ,
  UNIQUE INDEX `calon_siswa_UNIQUE1` (`tahunmasuk_id` ASC, `nomor_urut_pendaftaran` ASC) ,
  CONSTRAINT `fk_calon_siswa_fos_user1`
    FOREIGN KEY (`dibuat_oleh_id` )
    REFERENCES `fos_user` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_calon_siswa_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_calon_siswa_tahunmasuk1`
    FOREIGN KEY (`tahunmasuk_id` )
    REFERENCES `tahunmasuk` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_calon_siswa_gelombang1`
    FOREIGN KEY (`gelombang_id` )
    REFERENCES `gelombang` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_calon_siswa_fos_user2`
    FOREIGN KEY (`diubah_oleh_id` )
    REFERENCES `fos_user` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_calon_siswa_referensi1`
    FOREIGN KEY (`referensi_id` )
    REFERENCES `referensi` (`id` )
    ON DELETE SET NULL
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `calon_pembayaran_rutin` (
                            `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
                            `calon_siswa_id` INT(11) NOT NULL ,
                            `biaya_rutin_id` INT(11) NOT NULL ,
                            `nominal_pembayaran` BIGINT(20) NULL DEFAULT NULL ,
                            `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
                            `waktu_simpan` DATETIME NULL ,
                            `waktu_ubah` DATETIME NOT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_calon_pembayaran_rutin_biaya_rutin1_idx` (`biaya_rutin_id` ASC) ,
                            INDEX `fk_calon_pembayaran_rutin_calon_siswa1_idx` (`calon_siswa_id` ASC) ,
                            CONSTRAINT `fk_calon_pembayaran_rutin_biaya_rutin10`
                                FOREIGN KEY (`biaya_rutin_id` )
                                REFERENCES `biaya_rutin` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT,
                            CONSTRAINT `fk_calon_pembayaran_rutin_calon_siswa1`
                                FOREIGN KEY (`calon_siswa_id` )
                                REFERENCES `calon_siswa` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `calon_pembayaran_sekali` (
                            `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
                            `calon_siswa_id` INT(11) NOT NULL ,
                            `biaya_sekali_id` INT(11) NOT NULL ,
                            `nominal_pembayaran` BIGINT(20) NULL DEFAULT NULL ,
                            `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
                            `waktu_simpan` DATETIME NULL ,
                            `waktu_ubah` DATETIME NOT NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_calon_pembayaran_sekali_biaya_sekali1_idx` (`biaya_sekali_id` ASC) ,
                            INDEX `fk_calon_pembayaran_sekali_calon_siswa1_idx` (`calon_siswa_id` ASC) ,
                            CONSTRAINT `fk_calon_pembayaran_sekali_biaya_sekali10`
                                FOREIGN KEY (`biaya_sekali_id` )
                                REFERENCES `biaya_sekali` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT,
                            CONSTRAINT `fk_calon_pembayaran_sekali_calon_siswa1`
                                FOREIGN KEY (`calon_siswa_id` )
                                REFERENCES `calon_siswa` (`id` )
                                ON UPDATE RESTRICT)
                        ENGINE = InnoDB
                        DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `tahun` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(50) NULL DEFAULT NULL ,
  `kode` VARCHAR(45) NOT NULL ,
  `urutan` SMALLINT(6) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  `aktif` TINYINT(1) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_tahun_sekolah1_idx` (`sekolah_id` ASC) ,
  UNIQUE INDEX `idsekolah_kode_UNIQ` (`sekolah_id` ASC, `kode` ASC) ,
  CONSTRAINT `fk_tahun_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `semester` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(100) NULL DEFAULT NULL ,
  `kode` VARCHAR(50) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  `urutan` SMALLINT(6) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_semester_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_semester_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kelompok_mp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(400) NULL DEFAULT NULL ,
  `urutan` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_kelompok_mp_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_kelompok_mp_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `mata_pelajaran` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `kelompok_mp_id` INT(11) NOT NULL ,
  `nama` VARCHAR(400) NULL DEFAULT NULL ,
  `kode` VARCHAR(50) NULL DEFAULT NULL ,
  `penanggung_jawab` VARCHAR(400) NULL DEFAULT NULL ,
  `jumlah_jam` INT(11) NULL DEFAULT NULL ,
  `standar_kompetensi` VARCHAR(200) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_F16769B1AB51628D` (`kelompok_mp_id` ASC) ,
  CONSTRAINT `FK_F16769B1AB51628D`
    FOREIGN KEY (`kelompok_mp_id` )
    REFERENCES `kelompok_mp` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `cukil_mp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `mata_pelajaran_id` INT(11) NOT NULL ,
  `tahun_id` INT(11) NOT NULL ,
  `semester_id` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_E54128F8E01FF8FA` (`mata_pelajaran_id` ASC) ,
  INDEX `IDX_E54128F821691619` (`tahun_id` ASC) ,
  INDEX `IDX_E54128F85DEDC6E0` (`semester_id` ASC) ,
  CONSTRAINT `FK_E54128F821691619`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_E54128F85DEDC6E0`
    FOREIGN KEY (`semester_id` )
    REFERENCES `semester` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_E54128F8E01FF8FA`
    FOREIGN KEY (`mata_pelajaran_id` )
    REFERENCES `mata_pelajaran` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `guru_mp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `guru_id` INT(11) NOT NULL ,
  `mata_pelajaran_id` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_B0D7DF3D3B26D57A` (`guru_id` ASC) ,
  INDEX `IDX_B0D7DF3DE01FF8FA` (`mata_pelajaran_id` ASC) ,
  CONSTRAINT `FK_B0D7DF3D3B26D57A`
    FOREIGN KEY (`guru_id` )
    REFERENCES `guru` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_B0D7DF3DE01FF8FA`
    FOREIGN KEY (`mata_pelajaran_id` )
    REFERENCES `mata_pelajaran` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jenjang` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `kode` VARCHAR(45) NOT NULL ,
  `nama` VARCHAR(50) NULL DEFAULT NULL ,
  `urutan` SMALLINT(6) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_jenjang_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_jenjang_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kelas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `tahun_id` INT(11) NOT NULL ,
  `jenjang_id` INT(11) NOT NULL ,
  `nama` VARCHAR(300) NULL DEFAULT NULL ,
  `kode` VARCHAR(50) NOT NULL ,
  `keterangan` VARCHAR(400) NULL DEFAULT NULL ,
  `urutan` SMALLINT(6) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `kelas_unique1` (`sekolah_id` ASC, `kode` ASC) ,
  INDEX `fk_kelas_tahun1_idx` (`tahun_id` ASC) ,
  INDEX `fk_kelas_sekolah1_idx` (`sekolah_id` ASC) ,
  INDEX `fk_kelas_jenjang1_idx` (`jenjang_id` ASC) ,
  CONSTRAINT `fk_kelas_tahun1`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kelas_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kelas_jenjang1`
    FOREIGN KEY (`jenjang_id` )
    REFERENCES `jenjang` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jadwal_cmp` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `cukil_mp_id` INT(11) NOT NULL ,
  `guru_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `tanggal` DATETIME NULL DEFAULT NULL ,
  `jam` VARCHAR(100) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_D5FA61244F9460F5` (`cukil_mp_id` ASC) ,
  INDEX `IDX_D5FA61243B26D57A` (`guru_id` ASC) ,
  INDEX `fk_jadwal_cmp_kelas1_idx` (`kelas_id` ASC) ,
  CONSTRAINT `FK_D5FA61243B26D57A`
    FOREIGN KEY (`guru_id` )
    REFERENCES `guru` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_D5FA61244F9460F5`
    FOREIGN KEY (`cukil_mp_id` )
    REFERENCES `cukil_mp` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_jadwal_cmp_kelas1`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `guruhadir_jcmp` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `jadwal_cmp_id` BIGINT(20) NOT NULL ,
  `hadir` TINYINT(1) NULL DEFAULT NULL ,
  `jam_masuk` VARCHAR(20) NULL DEFAULT NULL ,
  `jam_keluar` VARCHAR(20) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_7926F66F18537C1E` (`jadwal_cmp_id` ASC) ,
  CONSTRAINT `FK_7926F66F18537C1E`
    FOREIGN KEY (`jadwal_cmp_id` )
    REFERENCES `jadwal_cmp` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jadwal_bel` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tahun_id` INT(11) NOT NULL ,
  `hari` INT(11) NULL DEFAULT NULL ,
  `dari_jam` VARCHAR(50) NULL DEFAULT NULL ,
  `hingga_jam` VARCHAR(50) NULL DEFAULT NULL ,
  `berulang` TINYINT(1) NOT NULL DEFAULT '0' ,
  `file` VARCHAR(100) NULL DEFAULT NULL ,
  `aktif` TINYINT(1) NOT NULL DEFAULT '1' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_jadwal_bel_tahun1_idx` (`tahun_id` ASC) ,
  CONSTRAINT `fk_jadwal_bel_tahun1`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `status_kehadiran_kepulangan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(50) NOT NULL ,
  `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_status_kehadiran_sekolah1_idx` (`sekolah_id` ASC) ,
  UNIQUE INDEX `uniq_idx1` (`sekolah_id` ASC, `nama` ASC) ,
  CONSTRAINT `fk_status_kehadiran_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `templatesms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(50) NULL DEFAULT NULL ,
  `teks` VARCHAR(500) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_templatesms_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_templatesms_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jadwal_kehadiran_kepulangan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tahun_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `status_kehadiran_kepulangan_id` INT(11) NOT NULL ,
  `templatesms_id` INT(11) NOT NULL ,
  `perulangan` ENUM('harian','mingguan','bulanan') NULL DEFAULT NULL ,
  `mingguan_hari_ke` SMALLINT(6) NULL DEFAULT NULL ,
  `bulanan_hari_ke` SMALLINT(6) NULL DEFAULT NULL ,
  `paramstatus_dari_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `paramstatus_hingga_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `sms_realtime_dari_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `sms_realtime_hingga_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `kirim_sms_realtime` TINYINT(1) NOT NULL DEFAULT '0' ,
  `command_realtime` VARCHAR(100) NULL DEFAULT NULL ,
  `sms_massal_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `kirim_sms_massal` TINYINT(1) NOT NULL DEFAULT '0' ,
  `command_massal` VARCHAR(100) NULL DEFAULT NULL ,
  `dari_jam` VARCHAR(50) NOT NULL DEFAULT '' ,
  `hingga_jam` VARCHAR(50) NULL DEFAULT '' ,
  `command_jadwal` VARCHAR(100) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_jadwal_kehadiran_kepulangan_tahun1_idx` (`tahun_id` ASC) ,
  INDEX `fk_jadwal_kehadiran_kepulangan_templatesms1_idx` (`templatesms_id` ASC) ,
  INDEX `fk_jadwal_kehadiran_kepulangan_status_kehadiran_kepulangan1_idx` (`status_kehadiran_kepulangan_id` ASC) ,
  INDEX `fk_jadwal_kehadiran_kepulangan_kelas1_idx` (`kelas_id` ASC) ,
  CONSTRAINT `fk_jadwal_kehadiran_kepulangan_status_kehadiran_kepulangan1`
    FOREIGN KEY (`status_kehadiran_kepulangan_id` )
    REFERENCES `status_kehadiran_kepulangan` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_jadwal_kehadiran_kepulangan_kelas1`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_jadwal_kehadiran_kepulangan_tahun1`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_jadwal_kehadiran_kepulangan_templatesms1`
    FOREIGN KEY (`templatesms_id` )
    REFERENCES `templatesms` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jenis_nilai` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tahun_id` INT(11) NOT NULL ,
  `nama` VARCHAR(300) NULL DEFAULT NULL ,
  `kode` VARCHAR(50) NULL DEFAULT NULL ,
  `bobot` INT(11) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_FC06E1AD21691619` (`tahun_id` ASC) ,
  CONSTRAINT `FK_FC06E1AD21691619`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kalender_pendidikan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `tanggal` DATE NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  `kbm` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_kalender_pendidikan_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_kalender_pendidikan_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kehadiran_guru` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `tanggal` DATE NULL DEFAULT NULL ,
  `hadir` TINYINT(1) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kehadiran_siswa` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `status_kehadiran_kepulangan_id` INT(11) NOT NULL ,
  `prioritas_pembaruan` SMALLINT(5) NOT NULL DEFAULT '0' ,
  `tanggal` DATE NULL DEFAULT NULL ,
  `jam` VARCHAR(10) NULL DEFAULT NULL ,
  `sms_dlr` SMALLINT(5) NULL DEFAULT NULL ,
  `sms_dlrtime` DATETIME NULL DEFAULT NULL ,
  `sms_terproses` TINYINT(1) NOT NULL DEFAULT '0' ,
  `keterangan_status` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `siswa_UNIQUE1` (`siswa_id` ASC, `tanggal` ASC) ,
  INDEX `fk_kehadiran_siswa_siswa1_idx` (`siswa_id` ASC) ,
  INDEX `fk_kehadiran_siswa_kelas1_idx` (`kelas_id` ASC) ,
  INDEX `fk_kehadiran_siswa_status_kehadiran_kepulangan1_idx` (`status_kehadiran_kepulangan_id` ASC) ,
  CONSTRAINT `fk_kehadiran_siswa_kelas1`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kehadiran_siswa_siswa1`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kehadiran_siswa_status_kehadiran_kepulangan1`
    FOREIGN KEY (`status_kehadiran_kepulangan_id` )
    REFERENCES `status_kehadiran_kepulangan` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `kepulangan_siswa` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `status_kehadiran_kepulangan_id` INT(11) NOT NULL ,
  `tanggal` DATE NULL DEFAULT NULL ,
  `jam` VARCHAR(10) NULL DEFAULT NULL ,
  `sms_pulang_terproses` TINYINT(1) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `siswa_UNIQUE1` (`siswa_id` ASC, `tanggal` ASC) ,
  INDEX `fk_kehadiran_siswa_siswa1_idx` (`siswa_id` ASC) ,
  INDEX `fk_kehadiran_siswa_kelas1_idx` (`kelas_id` ASC) ,
  INDEX `fk_kepulangan_siswa_status_kehadiran_kepulangan1_idx` (`status_kehadiran_kepulangan_id` ASC) ,
  CONSTRAINT `fk_kehadiran_siswa_kelas10`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kehadiran_siswa_siswa10`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_kepulangan_siswa_status_kehadiran_kepulangan1`
    FOREIGN KEY (`status_kehadiran_kepulangan_id` )
    REFERENCES `status_kehadiran_kepulangan` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `logfp` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `pin` VARCHAR(100) NULL DEFAULT NULL ,
  `datetime` DATETIME NULL DEFAULT NULL ,
  `status` SMALLINT(6) NULL DEFAULT NULL ,
  `ip` VARCHAR(50) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `logsms_keluar` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `ke` VARCHAR(50) NULL DEFAULT NULL ,
  `teks` VARCHAR(500) NULL DEFAULT NULL ,
  `dlr` TINYINT(1) NULL DEFAULT NULL ,
  `dlrtime` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `logsms_masuk` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `mesin_kehadiran` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `alamat_ip` VARCHAR(45) NOT NULL ,
  `commkey` VARCHAR(45) NOT NULL ,
  `aktif` TINYINT(1) NOT NULL DEFAULT '1' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_mesin_kehadiran_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_mesin_kehadiran_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `migration_versions` (
  `version` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  PRIMARY KEY (`version`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `orangtua_wali` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `nama` VARCHAR(300) NULL DEFAULT NULL ,
  `tempat_lahir` VARCHAR(300) NULL DEFAULT NULL ,
  `tanggal_lahir` DATE NULL DEFAULT NULL ,
  `kewarganegaraan` VARCHAR(200) NULL DEFAULT NULL ,
  `peran` INT(11) NULL DEFAULT NULL ,
  `pendidikan_tertinggi` VARCHAR(300) NULL DEFAULT NULL ,
  `pekerjaan` VARCHAR(300) NULL DEFAULT NULL ,
  `penghasilan_bulanan` INT(11) NULL DEFAULT NULL ,
  `penghasilan_tahunan` INT(11) NULL DEFAULT NULL ,
  `alamat` VARCHAR(400) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_258AE7B2E407D164` (`siswa_id` ASC) ,
  CONSTRAINT `FK_258AE7B2E407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `pembayaran_rutin` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `biaya_rutin_id` INT(11) NOT NULL ,
  `nominal_pembayaran` BIGINT(20) NOT NULL DEFAULT 0 ,
  `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
  `waktu_catat` DATETIME NULL DEFAULT NULL ,
  `waktu_ubah` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_692AC9CCE407D164` (`siswa_id` ASC) ,
  INDEX `fk_pembayaran_rutin_biaya_rutin1_idx` (`biaya_rutin_id` ASC) ,
  CONSTRAINT `FK_692AC9CCE407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_pembayaran_rutin_biaya_rutin1`
    FOREIGN KEY (`biaya_rutin_id` )
    REFERENCES `biaya_rutin` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `pembayaran_sekali` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `biaya_sekali_id` INT(11) NOT NULL ,
  `nominal_pembayaran` BIGINT(20) NOT NULL DEFAULT 0 ,
  `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
  `waktu_catat` DATETIME NULL DEFAULT NULL ,
  `waktu_ubah` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_EB34A2F7E407D164` (`siswa_id` ASC) ,
  INDEX `fk_pembayaran_sekali_biaya_sekali1_idx` (`biaya_sekali_id` ASC) ,
  CONSTRAINT `FK_EB34A2F7E407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_pembayaran_sekali_biaya_sekali1`
    FOREIGN KEY (`biaya_sekali_id` )
    REFERENCES `biaya_sekali` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `pendidikan_guru` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `guru_id` INT(11) NOT NULL ,
  `jenjang` INT(11) NULL DEFAULT NULL ,
  `nama` VARCHAR(400) NULL DEFAULT NULL ,
  `alamat` VARCHAR(500) NULL DEFAULT NULL ,
  `ijazah` VARCHAR(400) NULL DEFAULT NULL ,
  `kelulusan` VARCHAR(500) NULL DEFAULT NULL ,
  `tahunmasuk` DATE NULL DEFAULT NULL ,
  `tahunkeluar` DATE NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_E4B5B4943B26D57A` (`guru_id` ASC) ,
  CONSTRAINT `FK_E4B5B4943B26D57A`
    FOREIGN KEY (`guru_id` )
    REFERENCES `guru` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `pendidikan_siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `jenjang` INT(11) NULL DEFAULT NULL ,
  `nama` VARCHAR(400) NULL DEFAULT NULL ,
  `alamat` VARCHAR(500) NULL DEFAULT NULL ,
  `ijazah` VARCHAR(400) NULL DEFAULT NULL ,
  `ijazah_file` VARCHAR(300) NULL DEFAULT NULL ,
  `tahunmasuk` DATE NULL DEFAULT NULL ,
  `tahunkeluar` DATE NULL DEFAULT NULL ,
  `sttb_tanggal` DATE NULL DEFAULT NULL ,
  `sttb_no` VARCHAR(100) NULL DEFAULT NULL ,
  `sttb_file` VARCHAR(300) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_A36A8D7AE407D164` (`siswa_id` ASC) ,
  CONSTRAINT `FK_A36A8D7AE407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `penjurusan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(300) NULL DEFAULT NULL ,
  `kode` VARCHAR(50) NOT NULL ,
  `kepala` VARCHAR(400) NULL DEFAULT NULL ,
  `lft` INT(11) NULL DEFAULT NULL ,
  `lvl` INT(11) NULL DEFAULT NULL ,
  `rgt` INT(11) NULL DEFAULT NULL ,
  `root` INT(11) NULL DEFAULT NULL ,
  `parent_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unique_idx` (`sekolah_id` ASC, `kode` ASC) ,
  INDEX `fk_penjurusan_penjurusan1_idx` (`parent_id` ASC) ,
  INDEX `fk_penjurusan_sekolah1_idx` (`sekolah_id` ASC) ,
  CONSTRAINT `fk_penjurusan_penjurusan1`
    FOREIGN KEY (`parent_id` )
    REFERENCES `penjurusan` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_penjurusan_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `penyakit_siswa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `nama` VARCHAR(400) NULL DEFAULT NULL ,
  `kelas` VARCHAR(200) NULL DEFAULT NULL ,
  `tahun` VARCHAR(100) NULL DEFAULT NULL ,
  `lamasakit` VARCHAR(200) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_526CBF72E407D164` (`siswa_id` ASC) ,
  CONSTRAINT `FK_526CBF72E407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `siswa_cmp` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `cukil_mp_id` INT(11) NOT NULL ,
  `jenis_nilai_id` INT(11) NOT NULL ,
  `nilai` INT(11) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_D6DFF747E407D164` (`siswa_id` ASC) ,
  INDEX `IDX_D6DFF7474F9460F5` (`cukil_mp_id` ASC) ,
  INDEX `IDX_D6DFF7473AC79102` (`jenis_nilai_id` ASC) ,
  CONSTRAINT `FK_D6DFF7473AC79102`
    FOREIGN KEY (`jenis_nilai_id` )
    REFERENCES `jenis_nilai` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_D6DFF7474F9460F5`
    FOREIGN KEY (`cukil_mp_id` )
    REFERENCES `cukil_mp` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `FK_D6DFF747E407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `siswa_kelas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `tahun_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `penjurusan_id` INT(11) NULL DEFAULT NULL ,
  `aktif` TINYINT(1) NOT NULL DEFAULT '0' ,
  `keterangan` VARCHAR(400) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `siswa_kelas_unq1` (`siswa_id` ASC, `tahun_id` ASC, `kelas_id` ASC) ,
  INDEX `fk_siswa_kelas_siswa1_idx` (`siswa_id` ASC) ,
  INDEX `fk_siswa_kelas_tahun1_idx` (`tahun_id` ASC) ,
  INDEX `fk_siswa_kelas_kelas1_idx` (`kelas_id` ASC) ,
  INDEX `fk_siswa_kelas_penjurusan1_idx` (`penjurusan_id` ASC) ,
  CONSTRAINT `fk_siswa_kelas_kelas1`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_siswa_kelas_penjurusan1`
    FOREIGN KEY (`penjurusan_id` )
    REFERENCES `penjurusan` (`id` )
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_siswa_kelas_siswa1`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_siswa_kelas_tahun1`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `siswahadir_jcmp` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `jadwal_cmp_id` BIGINT(20) NOT NULL ,
  `hadir` TINYINT(1) NULL DEFAULT NULL ,
  `jam_masuk` VARCHAR(20) NULL DEFAULT NULL ,
  `jam_keluar` VARCHAR(20) NULL DEFAULT NULL ,
  `keterangan` VARCHAR(500) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_859D769E18537C1E` (`jadwal_cmp_id` ASC) ,
  CONSTRAINT `FK_859D769E18537C1E`
    FOREIGN KEY (`jadwal_cmp_id` )
    REFERENCES `jadwal_cmp` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `sms_kepulangan_siswa` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `sms_dlr` SMALLINT(5) NULL DEFAULT NULL ,
  `sms_dlrtime` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `tugas_akhir` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `siswa_id` INT(11) NOT NULL ,
  `judul` VARCHAR(300) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `IDX_35829604E407D164` (`siswa_id` ASC) ,
  CONSTRAINT `FK_35829604E407D164`
    FOREIGN KEY (`siswa_id` )
    REFERENCES `siswa` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `wali_kelas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tahun_id` INT(11) NOT NULL ,
  `kelas_id` INT(11) NOT NULL ,
  `nama` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_wali_kelas_tahun1_idx` (`tahun_id` ASC) ,
  INDEX `fk_wali_kelas_kelas1_idx` (`kelas_id` ASC) ,
  UNIQUE INDEX `wali_kelas_unq1` (`tahun_id` ASC, `kelas_id` ASC) ,
  CONSTRAINT `fk_wali_kelas_kelas1`
    FOREIGN KEY (`kelas_id` )
    REFERENCES `kelas` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_wali_kelas_tahun1`
    FOREIGN KEY (`tahun_id` )
    REFERENCES `tahun` (`id` )
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `jenis_imbalan` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `sekolah_id` INT(11) NOT NULL ,
  `nama` VARCHAR(45) NOT NULL ,
  `keterangan` VARCHAR(300) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_jenis_imbalan_sekolah1_idx` (`sekolah_id` ASC) ,
  UNIQUE INDEX `uniq_jenis_imbalan1` (`sekolah_id` ASC, `nama` ASC) ,
  CONSTRAINT `fk_jenis_imbalan_sekolah1`
    FOREIGN KEY (`sekolah_id` )
    REFERENCES `sekolah` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `imbalan_pendaftaran` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tahunmasuk_id` INT(11) NOT NULL ,
  `gelombang_id` INT(11) NOT NULL ,
  `jenis_imbalan_id` INT NOT NULL ,
  `nominal` INT(11) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_upah_pendaftaran_tahunmasuk1_idx` (`tahunmasuk_id` ASC) ,
  INDEX `fk_upah_pendaftaran_gelombang1_idx` (`gelombang_id` ASC) ,
  INDEX `fk_imbalan_pendaftaran_jenis_imbalan1_idx` (`jenis_imbalan_id` ASC) ,
  UNIQUE INDEX `uniq_imbalan_pendaftaran1` (`tahunmasuk_id` ASC, `gelombang_id` ASC, `jenis_imbalan_id` ASC) ,
  CONSTRAINT `fk_upah_pendaftaran_tahunmasuk1`
    FOREIGN KEY (`tahunmasuk_id` )
    REFERENCES `tahunmasuk` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_upah_pendaftaran_gelombang1`
    FOREIGN KEY (`gelombang_id` )
    REFERENCES `gelombang` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_imbalan_pendaftaran_jenis_imbalan1`
    FOREIGN KEY (`jenis_imbalan_id` )
    REFERENCES `jenis_imbalan` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `panitia_pendaftaran` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `tahunmasuk_id` INT(11) NOT NULL ,
  `panitia` LONGTEXT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_panitia_pendaftaran_tahunmasuk1_idx` (`tahunmasuk_id` ASC) ,
  UNIQUE INDEX `tahunmasuk_id_UNIQUE` (`tahunmasuk_id` ASC) ,
  CONSTRAINT `fk_panitia_pendaftaran_tahunmasuk1`
    FOREIGN KEY (`tahunmasuk_id` )
    REFERENCES `tahunmasuk` (`id` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;");

        $this
                ->addSql(
                        "CREATE  TABLE IF NOT EXISTS `calon_dokumen` (
                            `id` INT NOT NULL AUTO_INCREMENT ,
                            `calon_siswa_id` INT(11) NOT NULL ,
                            `nama_dokumen` VARCHAR(100) NULL ,
                            `ada` TINYINT(1) NULL ,
                            `file` VARCHAR(100) NULL ,
                            PRIMARY KEY (`id`) ,
                            INDEX `fk_calon_kelengkapan_dokumen_calon_siswa1_idx` (`calon_siswa_id` ASC) ,
                            CONSTRAINT `fk_calon_kelengkapan_dokumen_calon_siswa1`
                                FOREIGN KEY (`calon_siswa_id` )
                                REFERENCES `calon_siswa` (`id` )
                                ON DELETE RESTRICT
                                ON UPDATE RESTRICT)
                            ENGINE = InnoDB
                            DEFAULT CHARACTER SET = utf8;");

        $this->addSql($this->trigger1);
        $this->addSql($this->trigger2);
        $this->addSql($this->trigger3);
        $this->addSql($this->trigger4);
        $this->addSql($this->trigger5);
        $this->addSql($this->trigger6);
        $this->addSql($this->trigger7);
    }

    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this
                ->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql",
                        "Migration can only be executed safely on 'mysql'.");

        $this->addSql("DROP TABLE IF EXISTS `panitia_pendaftaran` ;");
        $this->addSql("DROP TABLE IF EXISTS `imbalan_pendaftaran` ;");
        $this->addSql("DROP TABLE IF EXISTS `jenis_imbalan` ;");
        $this->addSql("DROP TABLE IF EXISTS `wali_kelas` ;");
        $this->addSql("DROP TABLE IF EXISTS `tugas_akhir` ;");
        $this->addSql("DROP TABLE IF EXISTS `sms_kepulangan_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `siswahadir_jcmp` ;");
        $this->addSql("DROP TABLE IF EXISTS `siswa_kelas` ;");
        $this->addSql("DROP TABLE IF EXISTS `siswa_cmp` ;");
        $this->addSql("DROP TABLE IF EXISTS `penyakit_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `penjurusan` ;");
        $this->addSql("DROP TABLE IF EXISTS `pendidikan_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `pendidikan_guru` ;");
        $this->addSql("DROP TABLE IF EXISTS `pembayaran_sekali` ;");
        $this->addSql("DROP TABLE IF EXISTS `pembayaran_rutin` ;");
        $this->addSql("DROP TABLE IF EXISTS `orangtua_wali` ;");
        $this->addSql("DROP TABLE IF EXISTS `migration_versions` ;");
        $this->addSql("DROP TABLE IF EXISTS `mesin_kehadiran` ;");
        $this->addSql("DROP TABLE IF EXISTS `logsms_masuk` ;");
        $this->addSql("DROP TABLE IF EXISTS `logsms_keluar` ;");
        $this->addSql("DROP TABLE IF EXISTS `logfp` ;");
        $this->addSql("DROP TABLE IF EXISTS `kepulangan_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `kehadiran_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `kehadiran_guru` ;");
        $this->addSql("DROP TABLE IF EXISTS `kalender_pendidikan` ;");
        $this->addSql("DROP TABLE IF EXISTS `jenis_nilai` ;");
        $this->addSql("DROP TABLE IF EXISTS `jadwal_kehadiran_kepulangan` ;");
        $this->addSql("DROP TABLE IF EXISTS `templatesms` ;");
        $this->addSql("DROP TABLE IF EXISTS `status_kehadiran_kepulangan` ;");
        $this->addSql("DROP TABLE IF EXISTS `jadwal_bel` ;");
        $this->addSql("DROP TABLE IF EXISTS `guruhadir_jcmp` ;");
        $this->addSql("DROP TABLE IF EXISTS `jadwal_cmp` ;");
        $this->addSql("DROP TABLE IF EXISTS `kelas` ;");
        $this->addSql("DROP TABLE IF EXISTS `jenjang` ;");
        $this->addSql("DROP TABLE IF EXISTS `guru_mp` ;");
        $this->addSql("DROP TABLE IF EXISTS `cukil_mp` ;");
        $this->addSql("DROP TABLE IF EXISTS `mata_pelajaran` ;");
        $this->addSql("DROP TABLE IF EXISTS `kelompok_mp` ;");
        $this->addSql("DROP TABLE IF EXISTS `semester` ;");
        $this->addSql("DROP TABLE IF EXISTS `tahun` ;");
        $this->addSql("DROP TABLE IF EXISTS `calon_pembayaran_sekali` ;");
        $this->addSql("DROP TABLE IF EXISTS `calon_pembayaran_rutin` ;");
        $this->addSql("DROP TABLE IF EXISTS `calon_dokumen` ;");
        $this->addSql("DROP TABLE IF EXISTS `calon_siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `referensi` ;");
        $this->addSql("DROP TABLE IF EXISTS `fos_user` ;");
        $this->addSql("DROP TABLE IF EXISTS `staf` ;");
        $this->addSql("DROP TABLE IF EXISTS `siswa` ;");
        $this->addSql("DROP TABLE IF EXISTS `guru` ;");
        $this->addSql("DROP TABLE IF EXISTS `biaya_sekali` ;");
        $this->addSql("DROP TABLE IF EXISTS `biaya_rutin` ;");
        $this->addSql("DROP TABLE IF EXISTS `jenisbiaya` ;");
        $this->addSql("DROP TABLE IF EXISTS `tahunmasuk` ;");
        $this->addSql("DROP TABLE IF EXISTS `gelombang` ;");
        $this->addSql("DROP TABLE IF EXISTS `sekolah` ;");

        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertkelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeupdatekelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_calonsiswa`;");
    }
}
