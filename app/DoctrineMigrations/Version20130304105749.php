<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130304105749 extends AbstractMigration
{
    private $trigger1 = "DROP TRIGGER IF EXISTS `befins_calonpr`;
        DELIMITER //
        CREATE TRIGGER `befins_calonpr` BEFORE INSERT ON `calon_pembayaran_rutin`
         FOR EACH ROW BEGIN
            SET NEW.waktu_simpan = NOW();
        END
        //
        DELIMITER ;";
    
    private $trigger2 = "DROP TRIGGER IF EXISTS `befins_calonps`;
        DELIMITER //
        CREATE TRIGGER `befins_calonps` BEFORE INSERT ON `calon_pembayaran_sekali`
         FOR EACH ROW BEGIN
            SET NEW.waktu_simpan = NOW();
        END
        //
        DELIMITER ;";
    
    private $trigger3 = "DROP TRIGGER IF EXISTS `befins_calonsiswa`;
        DELIMITER //
        CREATE TRIGGER `befins_calonsiswa` BEFORE INSERT ON `calon_siswa`
         FOR EACH ROW BEGIN
            SET NEW.waktu_simpan = NOW();
        END
        //
        DELIMITER ;";
    
    private $trigger4 = "DROP TRIGGER IF EXISTS `beforeinsertkelas`;
        DELIMITER //
        CREATE TRIGGER `beforeinsertkelas` BEFORE INSERT ON `kelas`
         FOR EACH ROW BEGIN
            SET NEW.kode = CONCAT((SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id), NEW.kode);
        END
        //
        DELIMITER ;";
    
    private $trigger5 = "DROP TRIGGER IF EXISTS `beforeupdatekelas`;
        DELIMITER //
        CREATE TRIGGER `beforeupdatekelas` BEFORE UPDATE ON `kelas`
         FOR EACH ROW BEGIN
            DECLARE kodetahun VARCHAR(45);
            DECLARE kodekelas VARCHAR(50);
            
            SET kodetahun = (SELECT kode FROM tahun WHERE sekolah_id = NEW.sekolah_id AND id = NEW.tahun_id);
            SET kodekelas = NEW.kode;
            SET NEW.kode = IF(LEFT(kodekelas, LENGTH(kodetahun)) = kodetahun, kodekelas, CONCAT(kodetahun, NEW.kode));
        END
        //
        DELIMITER ;";
    
    private $trigger6 = "DROP TRIGGER IF EXISTS `beforeinsertsiswa`;
        DELIMITER //
        CREATE TRIGGER `beforeinsertsiswa` BEFORE INSERT ON `siswa`
         FOR EACH ROW BEGIN
            DECLARE nomorurutpersekolah INT;
        
            SET nomorurutpersekolah = (SELECT MAX(nomor_urut_persekolah) FROM siswa WHERE sekolah_id = NEW.sekolah_id);
            SET NEW.nomor_urut_persekolah = IFNULL(nomorurutpersekolah,100000) + 1;
            SET NEW.nomor_induk_sistem = CONCAT(CAST(NEW.nomor_urut_persekolah AS CHAR(6)), NEW.sekolah_id);
        END
        //
        DELIMITER ;";
    
    
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE referensi ADD email VARCHAR(100) DEFAULT NULL, DROP nomor_identitas");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_sekali CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE calon_pembayaran_rutin CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE calon_pembayaran_sekali CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE calon_siswa CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_rutin CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        
        $this->addSql($this->trigger1);
        $this->addSql($this->trigger2);
        $this->addSql($this->trigger3);
        $this->addSql($this->trigger4);
        $this->addSql($this->trigger5);
        $this->addSql($this->trigger6);
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql", "Migration can only be executed safely on 'mysql'.");
        
        $this->addSql("ALTER TABLE biaya_rutin CHANGE perulangan perulangan VARCHAR(255) DEFAULT 'bulan'");
        $this->addSql("ALTER TABLE calon_pembayaran_rutin CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE calon_pembayaran_sekali CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE calon_siswa CHANGE waktu_ubah waktu_ubah DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
        $this->addSql("ALTER TABLE imbalan_pendaftaran CHANGE nominal nominal INT DEFAULT 0 NOT NULL");
        $this->addSql("ALTER TABLE jadwal_bel CHANGE berulang berulang TINYINT(1) DEFAULT '0' NOT NULL, CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan CHANGE kirim_sms_realtime kirim_sms_realtime TINYINT(1) DEFAULT '0' NOT NULL, CHANGE kirim_sms_massal kirim_sms_massal TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kehadiran_siswa CHANGE prioritas_pembaruan prioritas_pembaruan SMALLINT DEFAULT '0' NOT NULL, CHANGE sms_terproses sms_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE kepulangan_siswa CHANGE sms_pulang_terproses sms_pulang_terproses TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE mesin_kehadiran CHANGE aktif aktif TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_rutin CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE pembayaran_sekali CHANGE nominal_pembayaran nominal_pembayaran BIGINT DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE referensi ADD nomor_identitas VARCHAR(300) DEFAULT NULL, DROP email");
        $this->addSql("ALTER TABLE siswa_kelas CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        $this->addSql("ALTER TABLE tahun CHANGE aktif aktif TINYINT(1) DEFAULT '0' NOT NULL");
        
        $this->addSql($this->trigger1);
        $this->addSql($this->trigger2);
        $this->addSql($this->trigger3);
        $this->addSql($this->trigger4);
        $this->addSql($this->trigger5);
        $this->addSql($this->trigger6);
    }
}
