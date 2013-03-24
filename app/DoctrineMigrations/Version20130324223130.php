<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130324223130 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("DROP TABLE dokumen_siswa");
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE panitia_pendaftaran CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE orangtua_wali CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE pilihan_cetak_kwitansi CHANGE output output VARCHAR(45) DEFAULT 'pdf' NOT NULL");
        $this->addSql("ALTER TABLE siswa CHANGE calon_siswa calon_siswa TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahunmasuk CHANGE tahun tahun VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE transaksi_pembayaran_pendaftaran DROP FOREIGN KEY fk_transaksi_pembayaran_pendaftaran_fos_user1");
        $this->addSql("DROP INDEX fk_transaksi_pembayaran_pendaftaran_fos_user1_idx ON transaksi_pembayaran_pendaftaran");
        $this->addSql("ALTER TABLE transaksi_pembayaran_pendaftaran DROP dibuat_oleh_id");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("CREATE TABLE dokumen_siswa (id INT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, nama_dokumen VARCHAR(100) DEFAULT NULL, ada TINYINT(1) DEFAULT '0' NOT NULL, file VARCHAR(100) DEFAULT NULL, INDEX fk_dokumen_siswa_siswa1_idx (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE dokumen_siswa ADD CONSTRAINT fk_dokumen_siswa_siswa1 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE orangtua_wali CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE panitia_pendaftaran CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pilihan_cetak_kwitansi CHANGE output output VARCHAR(45) DEFAULT 'pdf' NOT NULL");
        $this->addSql("ALTER TABLE siswa CHANGE calon_siswa calon_siswa TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahunmasuk CHANGE tahun tahun DATE NOT NULL");
        $this->addSql("ALTER TABLE transaksi_pembayaran_pendaftaran ADD dibuat_oleh_id INT NOT NULL");
        $this->addSql("ALTER TABLE transaksi_pembayaran_pendaftaran ADD CONSTRAINT fk_transaksi_pembayaran_pendaftaran_fos_user1 FOREIGN KEY (dibuat_oleh_id) REFERENCES fos_user (id)");
        $this->addSql("CREATE INDEX fk_transaksi_pembayaran_pendaftaran_fos_user1_idx ON transaksi_pembayaran_pendaftaran (dibuat_oleh_id)");
    }
}
