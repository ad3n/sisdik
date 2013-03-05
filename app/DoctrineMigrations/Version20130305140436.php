<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130305140436 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_sekali CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_rutin CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahunmasuk CHANGE tahun tahun VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_rutin CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_sekali CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahunmasuk CHANGE tahun tahun DATE NOT NULL");
    }
}
