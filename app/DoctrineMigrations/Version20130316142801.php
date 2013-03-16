<?php

namespace Application\Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration, Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130316142801 extends AbstractMigration
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

    private $trigger8 = "CREATE TRIGGER `befins_calontps`
BEFORE INSERT ON `calon_transaksi_pembayaran_sekali`
FOR EACH ROW
BEGIN
	SET NEW.waktu_simpan = NOW();
END";

    private $trigger9 = "CREATE TRIGGER `befup_calontps`
BEFORE UPDATE ON `calon_transaksi_pembayaran_sekali`
FOR EACH ROW
BEGIN
	SET NEW.nominal_pembayaran = OLD.nominal_pembayaran;
	SET NEW.keterangan = OLD.keterangan;
END";

    private $trigger10 = "CREATE TRIGGER `befins_calontpr`
BEFORE INSERT ON `calon_transaksi_pembayaran_rutin`
FOR EACH ROW
BEGIN
	SET NEW.waktu_simpan = NOW();
END";

    private $trigger11 = "CREATE TRIGGER `befup_calontpr`
BEFORE UPDATE ON `calon_transaksi_pembayaran_rutin`
FOR EACH ROW
BEGIN
	SET NEW.nominal_pembayaran = OLD.nominal_pembayaran;
	SET NEW.keterangan = OLD.keterangan;
END";

    public function up(Schema $schema) {
        $this->addSql($this->trigger1);
        $this->addSql($this->trigger2);
        $this->addSql($this->trigger3);
        $this->addSql($this->trigger4);
        $this->addSql($this->trigger5);
        $this->addSql($this->trigger6);
        $this->addSql($this->trigger7);
        $this->addSql($this->trigger8);
        $this->addSql($this->trigger9);
        $this->addSql($this->trigger10);
        $this->addSql($this->trigger11);
    }

    public function down(Schema $schema) {
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertkelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeupdatekelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_calonsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calontps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_calontps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calontpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_calontpr`;");
    }
}

