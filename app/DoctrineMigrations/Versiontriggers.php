<?php

namespace Application\Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration, Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Versiontriggers extends AbstractMigration
{

    private $beforeInsertSiswa = "CREATE TRIGGER `befin_siswa`
BEFORE INSERT ON `siswa`
FOR EACH ROW
BEGIN
    DECLARE nomorurutpendaftaran INT;
    DECLARE nomorurutpersekolah INT;

    SET nomorurutpendaftaran = (SELECT MAX(nomor_urut_pendaftaran) FROM siswa WHERE tahunmasuk_id = NEW.tahunmasuk_id);
    SET NEW.nomor_urut_pendaftaran = IFNULL(nomorurutpendaftaran, 0) + 1;
    SET NEW.nomor_pendaftaran =  CONCAT(CAST((SELECT tahun FROM tahunmasuk WHERE id = NEW.tahunmasuk_id) AS CHAR(4)), NEW.nomor_urut_pendaftaran);

    SET nomorurutpersekolah = (SELECT MAX(nomor_urut_persekolah) FROM siswa WHERE sekolah_id = NEW.sekolah_id);
    SET NEW.nomor_urut_persekolah = IFNULL(nomorurutpersekolah,100000) + 1;
    SET NEW.nomor_induk_sistem = CONCAT(CAST(NEW.nomor_urut_persekolah AS CHAR(6)), NEW.sekolah_id);

    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $beforeUpdateSiswa = "CREATE TRIGGER `befup_siswa`
BEFORE UPDATE ON `siswa`
FOR EACH ROW
BEGIN
    SET NEW.waktu_ubah = NOW();
END";

    private $beforeInsertPembayaranSekali = "CREATE TRIGGER `befin_ps`
BEFORE INSERT ON `pembayaran_sekali`
FOR EACH ROW
BEGIN
    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $beforeInsertPembayaranRutin = "CREATE TRIGGER `befin_pr`
BEFORE INSERT ON `pembayaran_rutin`
FOR EACH ROW
BEGIN
    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $beforeInsertTransaksiPembayaranSekali = "CREATE TRIGGER `befin_tps`
BEFORE INSERT ON `transaksi_pembayaran_sekali`
FOR EACH ROW
BEGIN
	SET NEW.waktu_simpan = NOW();
END";

    private $beforeUpdateTransaksiPembayaranSekali = "CREATE TRIGGER `befup_tps`
BEFORE UPDATE ON `transaksi_pembayaran_sekali`
FOR EACH ROW
BEGIN
	SET NEW.nominal_pembayaran = OLD.nominal_pembayaran;
	SET NEW.keterangan = OLD.keterangan;
END";

    private $beforeInsertTransaksiPembayaranRutin = "CREATE TRIGGER `befin_tpr`
BEFORE INSERT ON `transaksi_pembayaran_rutin`
FOR EACH ROW
BEGIN
	SET NEW.waktu_simpan = NOW();
END";

    private $beforeUpdateTransaksiPembayaranRutin = "CREATE TRIGGER `befup_tpr`
BEFORE UPDATE ON `transaksi_pembayaran_rutin`
FOR EACH ROW
BEGIN
	SET NEW.nominal_pembayaran = OLD.nominal_pembayaran;
	SET NEW.keterangan = OLD.keterangan;
END";

    private $beforeInsertKelas = "CREATE TRIGGER `befin_kelas`
BEFORE INSERT ON `kelas`
FOR EACH ROW
BEGIN
    SET NEW.kode = CONCAT((SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id), NEW.kode);
END";

    private $beforeUpdateKelas = "CREATE TRIGGER `befup_kelas`
BEFORE UPDATE ON `kelas`
FOR EACH ROW
BEGIN
    DECLARE kodetahun VARCHAR(45);
    DECLARE kodekelas VARCHAR(50);

    SET kodetahun = (SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id);
    SET kodekelas = NEW.kode;
    SET NEW.kode = IF(LEFT(kodekelas, LENGTH(kodetahun)) = kodetahun, kodekelas, CONCAT(kodetahun, NEW.kode));
END";

    public function up(Schema $schema) {
        $this->addSql($this->beforeInsertSiswa);
        $this->addSql($this->beforeUpdateSiswa);
        $this->addSql($this->beforeInsertPembayaranSekali);
        $this->addSql($this->beforeInsertPembayaranRutin);
        $this->addSql($this->beforeInsertTransaksiPembayaranSekali);
        $this->addSql($this->beforeUpdateTransaksiPembayaranSekali);
        $this->addSql($this->beforeInsertTransaksiPembayaranRutin);
        $this->addSql($this->beforeUpdateTransaksiPembayaranRutin);
        $this->addSql($this->beforeInsertKelas);
        $this->addSql($this->beforeUpdateKelas);
    }

    public function down(Schema $schema) {
        $this->addSql("DROP TRIGGER IF EXISTS `befin_siswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_siswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_pr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_ps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_tps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_tps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_tpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_tpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_kelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_kelas`;");
    }
}

