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

    SET nomorurutpendaftaran = (SELECT MAX(nomor_urut_pendaftaran) FROM siswa WHERE tahun_id = NEW.tahun_id);
    SET NEW.nomor_urut_pendaftaran = IFNULL(nomorurutpendaftaran, 0) + 1;
    SET NEW.nomor_pendaftaran =  CONCAT(CAST((SELECT tahun FROM tahun WHERE id = NEW.tahun_id) AS CHAR(4)), NEW.nomor_urut_pendaftaran);

    IF (NEW.calon_siswa = 0) THEN
        SET nomorurutpersekolah = (SELECT MAX(nomor_urut_persekolah) FROM siswa WHERE sekolah_id = NEW.sekolah_id);
        SET NEW.nomor_urut_persekolah = IFNULL(nomorurutpersekolah,100000) + 1;
        SET NEW.nomor_induk_sistem = CONCAT(CAST(NEW.nomor_urut_persekolah AS CHAR(6)), NEW.sekolah_id);
    END IF;

    SET NEW.waktu_simpan = NOW();
    SET NEW.waktu_ubah = NOW();
END";

    private $beforeInsertKelas = "CREATE TRIGGER `befin_kelas`
BEFORE INSERT ON `kelas`
FOR EACH ROW
BEGIN
    SET NEW.kode = CONCAT((SELECT kode FROM tahun_akademik WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_akademik_id), NEW.kode);
END";

    private $beforeUpdateKelas = "CREATE TRIGGER `befup_kelas`
BEFORE UPDATE ON `kelas`
FOR EACH ROW
BEGIN
    DECLARE kodetahun VARCHAR(45);
    DECLARE kodekelas VARCHAR(50);

    SET kodetahun = (SELECT kode FROM tahun_akademik WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_akademik_id);
    SET kodekelas = NEW.kode;
    SET NEW.kode = IF(LEFT(kodekelas, LENGTH(kodetahun)) = kodetahun, kodekelas, CONCAT(kodetahun, NEW.kode));
END";

    public function up(Schema $schema) {
        $this->addSql($this->beforeInsertSiswa);

        $this->addSql($this->beforeInsertKelas);
        $this->addSql($this->beforeUpdateKelas);
    }

    public function down(Schema $schema) {
        $this->addSql("DROP TRIGGER IF EXISTS `befin_siswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_siswa`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befin_pp`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_pr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befin_ps`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befin_tpp`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_tpp`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befin_tps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_tps`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befin_tpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_tpr`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befin_kelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befup_kelas`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befup_bp`;");

        $this->addSql("DROP TRIGGER IF EXISTS `befup_bs`;");
    }
}

