<?php

namespace Application\Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration, Doctrine\DBAL\Schema\Schema;

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
            SET NEW.waktu_ubah = NOW();
        END
        //
        DELIMITER ;";

    private $trigger2 = "DROP TRIGGER IF EXISTS `befins_calonps`;
        DELIMITER //
        CREATE TRIGGER `befins_calonps` BEFORE INSERT ON `calon_pembayaran_sekali`
         FOR EACH ROW BEGIN
            SET NEW.waktu_simpan = NOW();
            SET NEW.waktu_ubah = NOW();
        END
        //
        DELIMITER ;";

    private $trigger3 = "DROP TRIGGER IF EXISTS `befins_calonsiswa`;
        DELIMITER //
        CREATE TRIGGER `befins_calonsiswa` BEFORE INSERT ON `calon_siswa`
         FOR EACH ROW BEGIN
            SET NEW.waktu_simpan = NOW();
            SET NEW.waktu_ubah = NOW();
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

    public function up(Schema $schema) {
        // this up() migration is auto-generated, please modify it to your needs
        $this
                ->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql",
                        "Migration can only be executed safely on 'mysql'.");

        $this
                ->addSql(
                        "CREATE TABLE cukil_mp (id INT AUTO_INCREMENT NOT NULL, tahun_id INT NOT NULL, semester_id INT NOT NULL, mata_pelajaran_id INT NOT NULL, INDEX IDX_E54128F8F79579C8 (tahun_id), INDEX IDX_E54128F84A798B6F (semester_id), INDEX IDX_E54128F8C53475F3 (mata_pelajaran_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE sms_kepulangan_siswa (id BIGINT AUTO_INCREMENT NOT NULL, sms_dlr SMALLINT DEFAULT NULL, sms_dlrtime DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kehadiran_siswa (id BIGINT AUTO_INCREMENT NOT NULL, kelas_id INT NOT NULL, siswa_id INT NOT NULL, status_kehadiran_kepulangan_id INT NOT NULL, prioritas_pembaruan SMALLINT NOT NULL, tanggal DATE DEFAULT NULL, jam VARCHAR(10) DEFAULT NULL, sms_dlr SMALLINT DEFAULT NULL, sms_dlrtime DATETIME DEFAULT NULL, sms_terproses TINYINT(1) NOT NULL, keterangan_status VARCHAR(45) DEFAULT NULL, INDEX IDX_79FA6E66AC3824A (kelas_id), INDEX IDX_79FA6E66850D8FD8 (siswa_id), INDEX IDX_79FA6E661EA2E8A7 (status_kehadiran_kepulangan_id), UNIQUE INDEX siswa_UNIQUE1 (siswa_id, tanggal), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE pendidikan_siswa (id INT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, jenjang INT DEFAULT NULL, nama VARCHAR(400) DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, ijazah VARCHAR(400) DEFAULT NULL, ijazah_file VARCHAR(300) DEFAULT NULL, tahunmasuk DATE DEFAULT NULL, tahunkeluar DATE DEFAULT NULL, sttb_tanggal DATE DEFAULT NULL, sttb_no VARCHAR(100) DEFAULT NULL, sttb_file VARCHAR(300) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_A36A8D7A850D8FD8 (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE referensi (id INT AUTO_INCREMENT NOT NULL, nama VARCHAR(400) DEFAULT NULL, ponsel VARCHAR(50) DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE siswahadir_jcmp (id BIGINT AUTO_INCREMENT NOT NULL, jadwal_cmp_id BIGINT NOT NULL, hadir TINYINT(1) DEFAULT NULL, jam_masuk VARCHAR(20) DEFAULT NULL, jam_keluar VARCHAR(20) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_859D769E788B6A3D (jadwal_cmp_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE penyakit_siswa (id INT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, nama VARCHAR(400) DEFAULT NULL, kelas VARCHAR(200) DEFAULT NULL, tahun VARCHAR(100) DEFAULT NULL, lamasakit VARCHAR(200) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_526CBF72850D8FD8 (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jadwal_bel (id INT AUTO_INCREMENT NOT NULL, tahun_id INT NOT NULL, hari INT DEFAULT NULL, dari_jam VARCHAR(50) DEFAULT NULL, hingga_jam VARCHAR(50) DEFAULT NULL, berulang TINYINT(1) NOT NULL, file VARCHAR(100) DEFAULT NULL, aktif TINYINT(1) NOT NULL, INDEX IDX_8E0DD54F79579C8 (tahun_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE panitia_pendaftaran (id INT AUTO_INCREMENT NOT NULL, tahunmasuk_id INT NOT NULL, panitia LONGTEXT DEFAULT NULL, UNIQUE INDEX tahunmasuk_id_UNIQUE (tahunmasuk_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE siswa_kelas (id INT AUTO_INCREMENT NOT NULL, kelas_id INT NOT NULL, penjurusan_id INT DEFAULT NULL, siswa_id INT NOT NULL, tahun_id INT NOT NULL, aktif TINYINT(1) NOT NULL, keterangan VARCHAR(400) DEFAULT NULL, INDEX IDX_882A3907AC3824A (kelas_id), INDEX IDX_882A3907401A5AE2 (penjurusan_id), INDEX IDX_882A3907850D8FD8 (siswa_id), INDEX IDX_882A3907F79579C8 (tahun_id), UNIQUE INDEX siswa_kelas_unq1 (siswa_id, tahun_id, kelas_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jenis_imbalan (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(45) NOT NULL, keterangan VARCHAR(300) DEFAULT NULL, INDEX IDX_BC5CE818A48940F5 (sekolah_id), UNIQUE INDEX uniq_jenis_imbalan1 (sekolah_id, nama), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jenjang (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, kode VARCHAR(45) NOT NULL, nama VARCHAR(50) DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_F9B79FB2A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jadwal_sms (id INT AUTO_INCREMENT NOT NULL, tahun INT DEFAULT NULL, kelas INT DEFAULT NULL, templatesms INT DEFAULT NULL, perulangan VARCHAR(255) DEFAULT NULL, mingguan_hari_ke TINYINT(1) DEFAULT NULL, bulanan_hari_ke TINYINT(1) DEFAULT NULL, dari_jam VARCHAR(50) DEFAULT NULL, hingga_jam VARCHAR(50) DEFAULT NULL, kategori TINYINT(1) DEFAULT NULL, urutan_periksa SMALLINT DEFAULT NULL, INDEX IDX_50D593EEF76C7A00 (tahun), INDEX IDX_50D593EED01FFE54 (kelas), INDEX IDX_50D593EED332F0FF (templatesms), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE pembayaran_sekali (id BIGINT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, biaya_sekali_id INT NOT NULL, nominal_pembayaran BIGINT NOT NULL, keterangan VARCHAR(300) DEFAULT NULL, waktu_catat DATETIME DEFAULT NULL, waktu_ubah DATETIME DEFAULT NULL, INDEX IDX_EB34A2F7850D8FD8 (siswa_id), INDEX IDX_EB34A2F734F300B7 (biaya_sekali_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE logfp (id BIGINT AUTO_INCREMENT NOT NULL, pin VARCHAR(100) DEFAULT NULL, datetime DATETIME DEFAULT NULL, status SMALLINT DEFAULT NULL, ip VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jadwal_cmp (id BIGINT AUTO_INCREMENT NOT NULL, guru_id INT NOT NULL, cukil_mp_id INT NOT NULL, kelas_id INT NOT NULL, tanggal DATETIME DEFAULT NULL, jam VARCHAR(100) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_D5FA6124CE61CE44 (guru_id), INDEX IDX_D5FA612481103811 (cukil_mp_id), INDEX IDX_D5FA6124AC3824A (kelas_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kelompok_mp (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(400) DEFAULT NULL, urutan INT DEFAULT NULL, INDEX IDX_6D901222A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE calon_pembayaran_rutin (id BIGINT AUTO_INCREMENT NOT NULL, biaya_rutin_id INT NOT NULL, calon_siswa_id INT NOT NULL, nominal_pembayaran BIGINT DEFAULT NULL, keterangan VARCHAR(300) DEFAULT NULL, waktu_simpan DATETIME DEFAULT NULL, waktu_ubah DATETIME NOT NULL, INDEX IDX_CB0100B8675E095 (biaya_rutin_id), INDEX IDX_CB0100B9EFC794C (calon_siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE sekolah (id INT AUTO_INCREMENT NOT NULL, nama VARCHAR(300) NOT NULL, kode VARCHAR(50) NOT NULL, alamat VARCHAR(500) DEFAULT NULL, kodepos VARCHAR(10) DEFAULT NULL, telepon VARCHAR(50) DEFAULT NULL, fax VARCHAR(50) DEFAULT NULL, email VARCHAR(100) NOT NULL, norekening VARCHAR(100) DEFAULT NULL, bank VARCHAR(100) DEFAULT NULL, kepsek VARCHAR(400) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE tahun (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(50) DEFAULT NULL, kode VARCHAR(45) NOT NULL, urutan SMALLINT DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, aktif TINYINT(1) NOT NULL, INDEX IDX_F76C7A00A48940F5 (sekolah_id), UNIQUE INDEX idsekolah_kode_UNIQ (sekolah_id, kode), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE biaya_rutin (id INT AUTO_INCREMENT NOT NULL, gelombang_id INT NOT NULL, tahunmasuk_id INT NOT NULL, jenisbiaya_id INT NOT NULL, nominal BIGINT DEFAULT NULL, perulangan VARCHAR(255) DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_F4EDECBCC301FDF4 (gelombang_id), INDEX IDX_F4EDECBC7949DAB (tahunmasuk_id), INDEX IDX_F4EDECBCCC47F083 (jenisbiaya_id), UNIQUE INDEX UNQ_biaya_rutin1 (jenisbiaya_id, tahunmasuk_id, gelombang_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE imbalan_pendaftaran (id INT AUTO_INCREMENT NOT NULL, jenis_imbalan_id INT NOT NULL, gelombang_id INT NOT NULL, tahunmasuk_id INT NOT NULL, nominal INT NOT NULL, INDEX IDX_13653D69800E537F (jenis_imbalan_id), INDEX IDX_13653D69C301FDF4 (gelombang_id), INDEX IDX_13653D697949DAB (tahunmasuk_id), UNIQUE INDEX uniq_imbalan_pendaftaran1 (tahunmasuk_id, gelombang_id, jenis_imbalan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE fos_user (id INT AUTO_INCREMENT NOT NULL, guru_id INT DEFAULT NULL, sekolah_id INT DEFAULT NULL, siswa_id INT DEFAULT NULL, staf_id INT DEFAULT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked TINYINT(1) NOT NULL, expired TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)', credentials_expired TINYINT(1) NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_957A647992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_957A6479A0D96FBF (email_canonical), INDEX IDX_957A6479CE61CE44 (guru_id), INDEX IDX_957A6479A48940F5 (sekolah_id), INDEX IDX_957A6479850D8FD8 (siswa_
id), INDEX IDX_957A6479F340B01F (staf_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE gelombang (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(300) DEFAULT NULL, kode VARCHAR(50) DEFAULT NULL, keterangan VARCHAR(400) DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_628452B3A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE templatesms (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(50) DEFAULT NULL, teks VARCHAR(500) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_D332F0FFA48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jenis_nilai (id INT AUTO_INCREMENT NOT NULL, tahun_id INT NOT NULL, nama VARCHAR(300) DEFAULT NULL, kode VARCHAR(50) DEFAULT NULL, bobot INT DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_FC06E1ADF79579C8 (tahun_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE siswa_cmp (id BIGINT AUTO_INCREMENT NOT NULL, jenis_nilai_id INT NOT NULL, cukil_mp_id INT NOT NULL, siswa_id INT NOT NULL, nilai INT DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_D6DFF74719E76C67 (jenis_nilai_id), INDEX IDX_D6DFF74781103811 (cukil_mp_id), INDEX IDX_D6DFF747850D8FD8 (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE guruhadir (id BIGINT AUTO_INCREMENT NOT NULL, tanggal DATE DEFAULT NULL, hadir TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jadwal_kehadiran_kepulangan (id INT AUTO_INCREMENT NOT NULL, status_kehadiran_kepulangan_id INT NOT NULL, kelas_id INT NOT NULL, tahun_id INT NOT NULL, templatesms_id INT NOT NULL, perulangan VARCHAR(255) DEFAULT NULL, mingguan_hari_ke SMALLINT DEFAULT NULL, bulanan_hari_ke SMALLINT DEFAULT NULL, paramstatus_dari_jam VARCHAR(50) NOT NULL, paramstatus_hingga_jam VARCHAR(50) NOT NULL, sms_realtime_dari_jam VARCHAR(50) NOT NULL, sms_realtime_hingga_jam VARCHAR(50) NOT NULL, kirim_sms_realtime TINYINT(1) NOT NULL, command_realtime VARCHAR(100) DEFAULT NULL, sms_massal_jam VARCHAR(50) NOT NULL, kirim_sms_massal TINYINT(1) NOT NULL, command_massal VARCHAR(100) DEFAULT NULL, dari_jam VARCHAR(50) NOT NULL, hingga_jam VARCHAR(50) DEFAULT NULL, command_jadwal VARCHAR(100) DEFAULT NULL, INDEX IDX_C8CEBE281EA2E8A7 (status_kehadiran_kepulangan_id), INDEX IDX_C8CEBE28AC3824A (kelas_id), INDEX IDX_C8CEBE28F79579C8 (tahun_id), INDEX IDX_C8CEBE284B24133 (templatesms_id), PRIMARY KEY(id)) 
DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE tugas_akhir (id INT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, judul VARCHAR(300) DEFAULT NULL, INDEX IDX_35829604850D8FD8 (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE biaya_sekali (id INT AUTO_INCREMENT NOT NULL, gelombang_id INT NOT NULL, tahunmasuk_id INT NOT NULL, jenisbiaya_id INT NOT NULL, nominal BIGINT DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_BBAC14EEC301FDF4 (gelombang_id), INDEX IDX_BBAC14EE7949DAB (tahunmasuk_id), INDEX IDX_BBAC14EECC47F083 (jenisbiaya_id), UNIQUE INDEX UNQ_biaya_sekali1 (jenisbiaya_id, tahunmasuk_id, gelombang_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE logsms_masuk (id BIGINT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kepulangan_siswa (id BIGINT AUTO_INCREMENT NOT NULL, kelas_id INT NOT NULL, siswa_id INT NOT NULL, status_kehadiran_kepulangan_id INT NOT NULL, tanggal DATE DEFAULT NULL, jam VARCHAR(10) DEFAULT NULL, sms_pulang_terproses TINYINT(1) NOT NULL, INDEX IDX_3632BDD5AC3824A (kelas_id), INDEX IDX_3632BDD5850D8FD8 (siswa_id), INDEX IDX_3632BDD51EA2E8A7 (status_kehadiran_kepulangan_id), UNIQUE INDEX siswa_UNIQUE1 (siswa_id, tanggal), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE mata_pelajaran (id INT AUTO_INCREMENT NOT NULL, kelompok_mp_id INT NOT NULL, nama VARCHAR(400) DEFAULT NULL, kode VARCHAR(50) DEFAULT NULL, penanggung_jawab VARCHAR(400) DEFAULT NULL, jumlah_jam INT DEFAULT NULL, standar_kompetensi VARCHAR(200) DEFAULT NULL, INDEX IDX_F16769B1D573B444 (kelompok_mp_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE guru (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(400) DEFAULT NULL, jenis_kelamin VARCHAR(255) DEFAULT NULL, foto VARCHAR(400) DEFAULT NULL, agama INT DEFAULT NULL, tempat_lahir VARCHAR(400) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, telepon VARCHAR(100) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, nomor_induk VARCHAR(50) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, status TINYINT(1) DEFAULT NULL, INDEX IDX_E8E924DAA48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE calon_pembayaran_sekali (id BIGINT AUTO_INCREMENT NOT NULL, biaya_sekali_id INT NOT NULL, calon_siswa_id INT NOT NULL, nominal_pembayaran BIGINT DEFAULT NULL, keterangan VARCHAR(300) DEFAULT NULL, waktu_simpan DATETIME DEFAULT NULL, waktu_ubah DATETIME NOT NULL, INDEX IDX_EE516F3D34F300B7 (biaya_sekali_id), INDEX IDX_EE516F3D9EFC794C (calon_siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE calon_siswa (id INT AUTO_INCREMENT NOT NULL, referensi_id INT DEFAULT NULL, gelombang_id INT NOT NULL, diubah_oleh_id INT DEFAULT NULL, dibuat_oleh_id INT NOT NULL, sekolah_id INT NOT NULL, tahunmasuk_id INT NOT NULL, nomor_pendaftaran SMALLINT UNSIGNED DEFAULT NULL, nama_lengkap VARCHAR(300) DEFAULT NULL, jenis_kelamin VARCHAR(255) DEFAULT NULL, tempat_lahir VARCHAR(400) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, ponsel_orangtuawali VARCHAR(100) DEFAULT NULL, foto VARCHAR(100) DEFAULT NULL, waktu_simpan DATETIME DEFAULT NULL, waktu_ubah DATETIME NOT NULL, INDEX IDX_FD7929C63C73F693 (referensi_id), INDEX IDX_FD7929C6C301FDF4 (gelombang_id), INDEX IDX_FD7929C618ED1518 (diubah_oleh_id), INDEX IDX_FD7929C6AD547146 (dibuat_oleh_id), INDEX IDX_FD7929C6A48940F5 (sekolah_id), INDEX IDX_FD7929C67949DAB (tahunmasuk_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kalender_pendidikan (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, tanggal DATE DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, kbm TINYINT(1) NOT NULL, INDEX IDX_71F44582A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE logsms_keluar (id BIGINT AUTO_INCREMENT NOT NULL, ke VARCHAR(50) DEFAULT NULL, teks VARCHAR(500) DEFAULT NULL, dlr TINYINT(1) DEFAULT NULL, dlrtime DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kehadiran_guru (id BIGINT AUTO_INCREMENT NOT NULL, tanggal DATE DEFAULT NULL, hadir TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE orangtua_wali (id INT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, nama VARCHAR(300) DEFAULT NULL, tempat_lahir VARCHAR(300) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL, kewarganegaraan VARCHAR(200) DEFAULT NULL, peran INT DEFAULT NULL, pendidikan_tertinggi VARCHAR(300) DEFAULT NULL, pekerjaan VARCHAR(300) DEFAULT NULL, penghasilan_bulanan INT DEFAULT NULL, penghasilan_tahunan INT DEFAULT NULL, alamat VARCHAR(400) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_258AE7B2850D8FD8 (siswa_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE penjurusan (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, parent_id INT DEFAULT NULL, nama VARCHAR(300) DEFAULT NULL, kode VARCHAR(50) NOT NULL, kepala VARCHAR(400) DEFAULT NULL, lft INT DEFAULT NULL, lvl INT DEFAULT NULL, rgt INT DEFAULT NULL, root INT DEFAULT NULL, INDEX IDX_EDD737E4A48940F5 (sekolah_id), INDEX IDX_EDD737E4727ACA70 (parent_id), UNIQUE INDEX unique_idx (sekolah_id, kode), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE siswahadir (id BIGINT AUTO_INCREMENT NOT NULL, tanggal DATE DEFAULT NULL, hadir TINYINT(1) DEFAULT NULL, telat_dlr INT DEFAULT NULL, telat_dlrtime DATETIME DEFAULT NULL, absen_dlr INT DEFAULT NULL, absen_dlrtime DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE jenisbiaya (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(300) DEFAULT NULL, kode VARCHAR(50) DEFAULT NULL, keterangan VARCHAR(400) DEFAULT NULL, INDEX IDX_4DC35236A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE staf (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama_lengkap VARCHAR(300) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, INDEX IDX_D3018E69A48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE semester (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(100) DEFAULT NULL, kode VARCHAR(50) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_F7388EEDA48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE kelas (id INT AUTO_INCREMENT NOT NULL, tahun_id INT NOT NULL, sekolah_id INT NOT NULL, jenjang_id INT NOT NULL, nama VARCHAR(300) DEFAULT NULL, kode VARCHAR(50) NOT NULL, keterangan VARCHAR(400) DEFAULT NULL, urutan SMALLINT DEFAULT NULL, INDEX IDX_D01FFE54F79579C8 (tahun_id), INDEX IDX_D01FFE54A48940F5 (sekolah_id), INDEX IDX_D01FFE543475F4D (jenjang_id), UNIQUE INDEX kelas_unique1 (sekolah_id, kode), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE pendidikan_guru (id INT AUTO_INCREMENT NOT NULL, guru_id INT NOT NULL, jenjang INT DEFAULT NULL, nama VARCHAR(400) DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, ijazah VARCHAR(400) DEFAULT NULL, kelulusan VARCHAR(500) DEFAULT NULL, tahunmasuk DATE DEFAULT NULL, tahunkeluar DATE DEFAULT NULL, INDEX IDX_E4B5B494CE61CE44 (guru_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE guru_mp (id INT AUTO_INCREMENT NOT NULL, guru_id INT NOT NULL, mata_pelajaran_id INT NOT NULL, INDEX IDX_B0D7DF3DCE61CE44 (guru_id), INDEX IDX_B0D7DF3DC53475F3 (mata_pelajaran_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE pembayaran_rutin (id BIGINT AUTO_INCREMENT NOT NULL, siswa_id INT NOT NULL, biaya_rutin_id INT NOT NULL, nominal_pembayaran BIGINT NOT NULL, keterangan VARCHAR(300) DEFAULT NULL, waktu_catat DATETIME DEFAULT NULL, waktu_ubah DATETIME DEFAULT NULL, INDEX IDX_692AC9CC850D8FD8 (siswa_id), INDEX IDX_692AC9CC8675E095 (biaya_rutin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE siswa (id INT AUTO_INCREMENT NOT NULL, gelombang_id INT NOT NULL, tahunmasuk_id INT NOT NULL, sekolah_id INT NOT NULL, nomor_urut_persekolah INT UNSIGNED DEFAULT NULL, nomor_induk_sistem VARCHAR(45) DEFAULT NULL, nomor_pendaftaran SMALLINT UNSIGNED DEFAULT NULL, nomor_induk VARCHAR(100) DEFAULT NULL, nama_lengkap VARCHAR(300) DEFAULT NULL, jenis_kelamin VARCHAR(255) DEFAULT NULL, foto VARCHAR(400) DEFAULT NULL, agama VARCHAR(100) DEFAULT NULL, tempat_lahir VARCHAR(400) DEFAULT NULL, tanggal_lahir DATE DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, nama_panggilan VARCHAR(100) DEFAULT NULL, kewarganegaraan VARCHAR(200) DEFAULT NULL, anak_ke INT DEFAULT NULL, jumlah_saudarakandung INT DEFAULT NULL, jumlah_saudaratiri INT DEFAULT NULL, status_orphan VARCHAR(100) DEFAULT NULL, bahasa_seharihari VARCHAR(200) DEFAULT NULL, alamat VARCHAR(500) DEFAULT NULL, kodepos VARCHAR(30) DEFAULT NULL, telepon VARCHAR(100) DEFAULT NULL, ponsel_siswa VARCHAR(100) DEFAULT NULL, ponsel_
orangtuawali VARCHAR(100) DEFAULT NULL, sekolah_tinggaldi VARCHAR(400) DEFAULT NULL, jarak_tempat VARCHAR(300) DEFAULT NULL, cara_kesekolah VARCHAR(300) DEFAULT NULL, beratbadan INT DEFAULT NULL, tinggibadan INT DEFAULT NULL, golongandarah VARCHAR(50) DEFAULT NULL, INDEX IDX_3202BD7DC301FDF4 (gelombang_id), INDEX IDX_3202BD7D7949DAB (tahunmasuk_id), INDEX IDX_3202BD7DA48940F5 (sekolah_id), UNIQUE INDEX UNIQ_siswa1 (sekolah_id, nomor_urut_persekolah), UNIQUE INDEX nomor_induk_sistem_UNIQUE (nomor_induk_sistem), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE tahunmasuk (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, tahun DATE NOT NULL, INDEX IDX_E437322BA48940F5 (sekolah_id), UNIQUE INDEX UNIQ_tahunmasuk1 (sekolah_id, tahun), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE guruhadir_jcmp (id BIGINT AUTO_INCREMENT NOT NULL, jadwal_cmp_id BIGINT NOT NULL, hadir TINYINT(1) DEFAULT NULL, jam_masuk VARCHAR(20) DEFAULT NULL, jam_keluar VARCHAR(20) DEFAULT NULL, keterangan VARCHAR(500) DEFAULT NULL, INDEX IDX_7926F66F788B6A3D (jadwal_cmp_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE status_kehadiran_kepulangan (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, nama VARCHAR(50) NOT NULL, keterangan VARCHAR(300) DEFAULT NULL, INDEX IDX_2F04B584A48940F5 (sekolah_id), UNIQUE INDEX uniq_idx1 (sekolah_id, nama), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE wali_kelas (id INT AUTO_INCREMENT NOT NULL, kelas_id INT NOT NULL, tahun_id INT NOT NULL, nama VARCHAR(45) DEFAULT NULL, INDEX IDX_CE886CD9AC3824A (kelas_id), INDEX IDX_CE886CD9F79579C8 (tahun_id), UNIQUE INDEX wali_kelas_unq1 (tahun_id, kelas_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "CREATE TABLE mesin_kehadiran (id INT AUTO_INCREMENT NOT NULL, sekolah_id INT NOT NULL, alamat_ip VARCHAR(45) NOT NULL, commkey VARCHAR(45) NOT NULL, aktif TINYINT(1) NOT NULL, INDEX IDX_F2C5F3CA48940F5 (sekolah_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB");
        $this
                ->addSql(
                        "ALTER TABLE cukil_mp ADD CONSTRAINT FK_E54128F8F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE cukil_mp ADD CONSTRAINT FK_E54128F84A798B6F FOREIGN KEY (semester_id) REFERENCES semester (id)");
        $this
                ->addSql(
                        "ALTER TABLE cukil_mp ADD CONSTRAINT FK_E54128F8C53475F3 FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran (id)");
        $this
                ->addSql(
                        "ALTER TABLE kehadiran_siswa ADD CONSTRAINT FK_79FA6E66AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE kehadiran_siswa ADD CONSTRAINT FK_79FA6E66850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE kehadiran_siswa ADD CONSTRAINT FK_79FA6E661EA2E8A7 FOREIGN KEY (status_kehadiran_kepulangan_id) REFERENCES status_kehadiran_kepulangan (id)");
        $this
                ->addSql(
                        "ALTER TABLE pendidikan_siswa ADD CONSTRAINT FK_A36A8D7A850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswahadir_jcmp ADD CONSTRAINT FK_859D769E788B6A3D FOREIGN KEY (jadwal_cmp_id) REFERENCES jadwal_cmp (id)");
        $this
                ->addSql(
                        "ALTER TABLE penyakit_siswa ADD CONSTRAINT FK_526CBF72850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_bel ADD CONSTRAINT FK_8E0DD54F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE panitia_pendaftaran ADD CONSTRAINT FK_BB548D617949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_kelas ADD CONSTRAINT FK_882A3907AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_kelas ADD CONSTRAINT FK_882A3907401A5AE2 FOREIGN KEY (penjurusan_id) REFERENCES penjurusan (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_kelas ADD CONSTRAINT FK_882A3907850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_kelas ADD CONSTRAINT FK_882A3907F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE jenis_imbalan ADD CONSTRAINT FK_BC5CE818A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE jenjang ADD CONSTRAINT FK_F9B79FB2A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_sms ADD CONSTRAINT FK_50D593EEF76C7A00 FOREIGN KEY (tahun) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_sms ADD CONSTRAINT FK_50D593EED01FFE54 FOREIGN KEY (kelas) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_sms ADD CONSTRAINT FK_50D593EED332F0FF FOREIGN KEY (templatesms) REFERENCES templatesms (id)");
        $this
                ->addSql(
                        "ALTER TABLE pembayaran_sekali ADD CONSTRAINT FK_EB34A2F7850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE pembayaran_sekali ADD CONSTRAINT FK_EB34A2F734F300B7 FOREIGN KEY (biaya_sekali_id) REFERENCES biaya_sekali (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_cmp ADD CONSTRAINT FK_D5FA6124CE61CE44 FOREIGN KEY (guru_id) REFERENCES guru (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_cmp ADD CONSTRAINT FK_D5FA612481103811 FOREIGN KEY (cukil_mp_id) REFERENCES cukil_mp (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_cmp ADD CONSTRAINT FK_D5FA6124AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE kelompok_mp ADD CONSTRAINT FK_6D901222A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_pembayaran_rutin ADD CONSTRAINT FK_CB0100B8675E095 FOREIGN KEY (biaya_rutin_id) REFERENCES biaya_rutin (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_pembayaran_rutin ADD CONSTRAINT FK_CB0100B9EFC794C FOREIGN KEY (calon_siswa_id) REFERENCES calon_siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE tahun ADD CONSTRAINT FK_F76C7A00A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_rutin ADD CONSTRAINT FK_F4EDECBCC301FDF4 FOREIGN KEY (gelombang_id) REFERENCES gelombang (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_rutin ADD CONSTRAINT FK_F4EDECBC7949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_rutin ADD CONSTRAINT FK_F4EDECBCCC47F083 FOREIGN KEY (jenisbiaya_id) REFERENCES jenisbiaya (id)");
        $this
                ->addSql(
                        "ALTER TABLE imbalan_pendaftaran ADD CONSTRAINT FK_13653D69800E537F FOREIGN KEY (jenis_imbalan_id) REFERENCES jenis_imbalan (id)");
        $this
                ->addSql(
                        "ALTER TABLE imbalan_pendaftaran ADD CONSTRAINT FK_13653D69C301FDF4 FOREIGN KEY (gelombang_id) REFERENCES gelombang (id)");
        $this
                ->addSql(
                        "ALTER TABLE imbalan_pendaftaran ADD CONSTRAINT FK_13653D697949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479CE61CE44 FOREIGN KEY (guru_id) REFERENCES guru (id)");
        $this
                ->addSql(
                        "ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479F340B01F FOREIGN KEY (staf_id) REFERENCES staf (id)");
        $this
                ->addSql(
                        "ALTER TABLE gelombang ADD CONSTRAINT FK_628452B3A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE templatesms ADD CONSTRAINT FK_D332F0FFA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE jenis_nilai ADD CONSTRAINT FK_FC06E1ADF79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_cmp ADD CONSTRAINT FK_D6DFF74719E76C67 FOREIGN KEY (jenis_nilai_id) REFERENCES jenis_nilai (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_cmp ADD CONSTRAINT FK_D6DFF74781103811 FOREIGN KEY (cukil_mp_id) REFERENCES cukil_mp (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa_cmp ADD CONSTRAINT FK_D6DFF747850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_kehadiran_kepulangan ADD CONSTRAINT FK_C8CEBE281EA2E8A7 FOREIGN KEY (status_kehadiran_kepulangan_id) REFERENCES status_kehadiran_kepulangan (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_kehadiran_kepulangan ADD CONSTRAINT FK_C8CEBE28AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_kehadiran_kepulangan ADD CONSTRAINT FK_C8CEBE28F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE jadwal_kehadiran_kepulangan ADD CONSTRAINT FK_C8CEBE284B24133 FOREIGN KEY (templatesms_id) REFERENCES templatesms (id)");
        $this
                ->addSql(
                        "ALTER TABLE tugas_akhir ADD CONSTRAINT FK_35829604850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_sekali ADD CONSTRAINT FK_BBAC14EEC301FDF4 FOREIGN KEY (gelombang_id) REFERENCES gelombang (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_sekali ADD CONSTRAINT FK_BBAC14EE7949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE biaya_sekali ADD CONSTRAINT FK_BBAC14EECC47F083 FOREIGN KEY (jenisbiaya_id) REFERENCES jenisbiaya (id)");
        $this
                ->addSql(
                        "ALTER TABLE kepulangan_siswa ADD CONSTRAINT FK_3632BDD5AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE kepulangan_siswa ADD CONSTRAINT FK_3632BDD5850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE kepulangan_siswa ADD CONSTRAINT FK_3632BDD51EA2E8A7 FOREIGN KEY (status_kehadiran_kepulangan_id) REFERENCES status_kehadiran_kepulangan (id)");
        $this
                ->addSql(
                        "ALTER TABLE mata_pelajaran ADD CONSTRAINT FK_F16769B1D573B444 FOREIGN KEY (kelompok_mp_id) REFERENCES kelompok_mp (id)");
        $this
                ->addSql(
                        "ALTER TABLE guru ADD CONSTRAINT FK_E8E924DAA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_pembayaran_sekali ADD CONSTRAINT FK_EE516F3D34F300B7 FOREIGN KEY (biaya_sekali_id) REFERENCES biaya_sekali (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_pembayaran_sekali ADD CONSTRAINT FK_EE516F3D9EFC794C FOREIGN KEY (calon_siswa_id) REFERENCES calon_siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C63C73F693 FOREIGN KEY (referensi_id) REFERENCES referensi (id) ON DELETE SET NULL");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C6C301FDF4 FOREIGN KEY (gelombang_id) REFERENCES gelombang (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C618ED1518 FOREIGN KEY (diubah_oleh_id) REFERENCES fos_user (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C6AD547146 FOREIGN KEY (dibuat_oleh_id) REFERENCES fos_user (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C6A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE calon_siswa ADD CONSTRAINT FK_FD7929C67949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE kalender_pendidikan ADD CONSTRAINT FK_71F44582A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE orangtua_wali ADD CONSTRAINT FK_258AE7B2850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE penjurusan ADD CONSTRAINT FK_EDD737E4A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE penjurusan ADD CONSTRAINT FK_EDD737E4727ACA70 FOREIGN KEY (parent_id) REFERENCES penjurusan (id)");
        $this
                ->addSql(
                        "ALTER TABLE jenisbiaya ADD CONSTRAINT FK_4DC35236A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE staf ADD CONSTRAINT FK_D3018E69A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE semester ADD CONSTRAINT FK_F7388EEDA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE kelas ADD CONSTRAINT FK_D01FFE54F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE kelas ADD CONSTRAINT FK_D01FFE54A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE kelas ADD CONSTRAINT FK_D01FFE543475F4D FOREIGN KEY (jenjang_id) REFERENCES jenjang (id)");
        $this
                ->addSql(
                        "ALTER TABLE pendidikan_guru ADD CONSTRAINT FK_E4B5B494CE61CE44 FOREIGN KEY (guru_id) REFERENCES guru (id)");
        $this
                ->addSql(
                        "ALTER TABLE guru_mp ADD CONSTRAINT FK_B0D7DF3DCE61CE44 FOREIGN KEY (guru_id) REFERENCES guru (id)");
        $this
                ->addSql(
                        "ALTER TABLE guru_mp ADD CONSTRAINT FK_B0D7DF3DC53475F3 FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran (id)");
        $this
                ->addSql(
                        "ALTER TABLE pembayaran_rutin ADD CONSTRAINT FK_692AC9CC850D8FD8 FOREIGN KEY (siswa_id) REFERENCES siswa (id)");
        $this
                ->addSql(
                        "ALTER TABLE pembayaran_rutin ADD CONSTRAINT FK_692AC9CC8675E095 FOREIGN KEY (biaya_rutin_id) REFERENCES biaya_rutin (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa ADD CONSTRAINT FK_3202BD7DC301FDF4 FOREIGN KEY (gelombang_id) REFERENCES gelombang (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa ADD CONSTRAINT FK_3202BD7D7949DAB FOREIGN KEY (tahunmasuk_id) REFERENCES tahunmasuk (id)");
        $this
                ->addSql(
                        "ALTER TABLE siswa ADD CONSTRAINT FK_3202BD7DA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE tahunmasuk ADD CONSTRAINT FK_E437322BA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE guruhadir_jcmp ADD CONSTRAINT FK_7926F66F788B6A3D FOREIGN KEY (jadwal_cmp_id) REFERENCES jadwal_cmp (id)");
        $this
                ->addSql(
                        "ALTER TABLE status_kehadiran_kepulangan ADD CONSTRAINT FK_2F04B584A48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");
        $this
                ->addSql(
                        "ALTER TABLE wali_kelas ADD CONSTRAINT FK_CE886CD9AC3824A FOREIGN KEY (kelas_id) REFERENCES kelas (id)");
        $this
                ->addSql(
                        "ALTER TABLE wali_kelas ADD CONSTRAINT FK_CE886CD9F79579C8 FOREIGN KEY (tahun_id) REFERENCES tahun (id)");
        $this
                ->addSql(
                        "ALTER TABLE mesin_kehadiran ADD CONSTRAINT FK_F2C5F3CA48940F5 FOREIGN KEY (sekolah_id) REFERENCES sekolah (id)");

        $this->addSql($this->trigger1);
        $this->addSql($this->trigger2);
        $this->addSql($this->trigger3);
        $this->addSql($this->trigger4);
        $this->addSql($this->trigger5);
        $this->addSql($this->trigger6);
    }

    public function down(Schema $schema) {
        // this down() migration is auto-generated, please modify it to your needs
        $this
                ->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql",
                        "Migration can only be executed safely on 'mysql'.");

        $this->addSql("ALTER TABLE jadwal_cmp DROP FOREIGN KEY FK_D5FA612481103811");
        $this->addSql("ALTER TABLE siswa_cmp DROP FOREIGN KEY FK_D6DFF74781103811");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C63C73F693");
        $this->addSql("ALTER TABLE imbalan_pendaftaran DROP FOREIGN KEY FK_13653D69800E537F");
        $this->addSql("ALTER TABLE kelas DROP FOREIGN KEY FK_D01FFE543475F4D");
        $this->addSql("ALTER TABLE siswahadir_jcmp DROP FOREIGN KEY FK_859D769E788B6A3D");
        $this->addSql("ALTER TABLE guruhadir_jcmp DROP FOREIGN KEY FK_7926F66F788B6A3D");
        $this->addSql("ALTER TABLE mata_pelajaran DROP FOREIGN KEY FK_F16769B1D573B444");
        $this->addSql("ALTER TABLE jenis_imbalan DROP FOREIGN KEY FK_BC5CE818A48940F5");
        $this->addSql("ALTER TABLE jenjang DROP FOREIGN KEY FK_F9B79FB2A48940F5");
        $this->addSql("ALTER TABLE kelompok_mp DROP FOREIGN KEY FK_6D901222A48940F5");
        $this->addSql("ALTER TABLE tahun DROP FOREIGN KEY FK_F76C7A00A48940F5");
        $this->addSql("ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479A48940F5");
        $this->addSql("ALTER TABLE gelombang DROP FOREIGN KEY FK_628452B3A48940F5");
        $this->addSql("ALTER TABLE templatesms DROP FOREIGN KEY FK_D332F0FFA48940F5");
        $this->addSql("ALTER TABLE guru DROP FOREIGN KEY FK_E8E924DAA48940F5");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C6A48940F5");
        $this->addSql("ALTER TABLE kalender_pendidikan DROP FOREIGN KEY FK_71F44582A48940F5");
        $this->addSql("ALTER TABLE penjurusan DROP FOREIGN KEY FK_EDD737E4A48940F5");
        $this->addSql("ALTER TABLE jenisbiaya DROP FOREIGN KEY FK_4DC35236A48940F5");
        $this->addSql("ALTER TABLE staf DROP FOREIGN KEY FK_D3018E69A48940F5");
        $this->addSql("ALTER TABLE semester DROP FOREIGN KEY FK_F7388EEDA48940F5");
        $this->addSql("ALTER TABLE kelas DROP FOREIGN KEY FK_D01FFE54A48940F5");
        $this->addSql("ALTER TABLE siswa DROP FOREIGN KEY FK_3202BD7DA48940F5");
        $this->addSql("ALTER TABLE tahunmasuk DROP FOREIGN KEY FK_E437322BA48940F5");
        $this->addSql("ALTER TABLE status_kehadiran_kepulangan DROP FOREIGN KEY FK_2F04B584A48940F5");
        $this->addSql("ALTER TABLE mesin_kehadiran DROP FOREIGN KEY FK_F2C5F3CA48940F5");
        $this->addSql("ALTER TABLE cukil_mp DROP FOREIGN KEY FK_E54128F8F79579C8");
        $this->addSql("ALTER TABLE jadwal_bel DROP FOREIGN KEY FK_8E0DD54F79579C8");
        $this->addSql("ALTER TABLE siswa_kelas DROP FOREIGN KEY FK_882A3907F79579C8");
        $this->addSql("ALTER TABLE jadwal_sms DROP FOREIGN KEY FK_50D593EEF76C7A00");
        $this->addSql("ALTER TABLE jenis_nilai DROP FOREIGN KEY FK_FC06E1ADF79579C8");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan DROP FOREIGN KEY FK_C8CEBE28F79579C8");
        $this->addSql("ALTER TABLE kelas DROP FOREIGN KEY FK_D01FFE54F79579C8");
        $this->addSql("ALTER TABLE wali_kelas DROP FOREIGN KEY FK_CE886CD9F79579C8");
        $this->addSql("ALTER TABLE calon_pembayaran_rutin DROP FOREIGN KEY FK_CB0100B8675E095");
        $this->addSql("ALTER TABLE pembayaran_rutin DROP FOREIGN KEY FK_692AC9CC8675E095");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C618ED1518");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C6AD547146");
        $this->addSql("ALTER TABLE biaya_rutin DROP FOREIGN KEY FK_F4EDECBCC301FDF4");
        $this->addSql("ALTER TABLE imbalan_pendaftaran DROP FOREIGN KEY FK_13653D69C301FDF4");
        $this->addSql("ALTER TABLE biaya_sekali DROP FOREIGN KEY FK_BBAC14EEC301FDF4");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C6C301FDF4");
        $this->addSql("ALTER TABLE siswa DROP FOREIGN KEY FK_3202BD7DC301FDF4");
        $this->addSql("ALTER TABLE jadwal_sms DROP FOREIGN KEY FK_50D593EED332F0FF");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan DROP FOREIGN KEY FK_C8CEBE284B24133");
        $this->addSql("ALTER TABLE siswa_cmp DROP FOREIGN KEY FK_D6DFF74719E76C67");
        $this->addSql("ALTER TABLE pembayaran_sekali DROP FOREIGN KEY FK_EB34A2F734F300B7");
        $this->addSql("ALTER TABLE calon_pembayaran_sekali DROP FOREIGN KEY FK_EE516F3D34F300B7");
        $this->addSql("ALTER TABLE cukil_mp DROP FOREIGN KEY FK_E54128F8C53475F3");
        $this->addSql("ALTER TABLE guru_mp DROP FOREIGN KEY FK_B0D7DF3DC53475F3");
        $this->addSql("ALTER TABLE jadwal_cmp DROP FOREIGN KEY FK_D5FA6124CE61CE44");
        $this->addSql("ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479CE61CE44");
        $this->addSql("ALTER TABLE pendidikan_guru DROP FOREIGN KEY FK_E4B5B494CE61CE44");
        $this->addSql("ALTER TABLE guru_mp DROP FOREIGN KEY FK_B0D7DF3DCE61CE44");
        $this->addSql("ALTER TABLE calon_pembayaran_rutin DROP FOREIGN KEY FK_CB0100B9EFC794C");
        $this->addSql("ALTER TABLE calon_pembayaran_sekali DROP FOREIGN KEY FK_EE516F3D9EFC794C");
        $this->addSql("ALTER TABLE siswa_kelas DROP FOREIGN KEY FK_882A3907401A5AE2");
        $this->addSql("ALTER TABLE penjurusan DROP FOREIGN KEY FK_EDD737E4727ACA70");
        $this->addSql("ALTER TABLE biaya_rutin DROP FOREIGN KEY FK_F4EDECBCCC47F083");
        $this->addSql("ALTER TABLE biaya_sekali DROP FOREIGN KEY FK_BBAC14EECC47F083");
        $this->addSql("ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479F340B01F");
        $this->addSql("ALTER TABLE cukil_mp DROP FOREIGN KEY FK_E54128F84A798B6F");
        $this->addSql("ALTER TABLE kehadiran_siswa DROP FOREIGN KEY FK_79FA6E66AC3824A");
        $this->addSql("ALTER TABLE siswa_kelas DROP FOREIGN KEY FK_882A3907AC3824A");
        $this->addSql("ALTER TABLE jadwal_sms DROP FOREIGN KEY FK_50D593EED01FFE54");
        $this->addSql("ALTER TABLE jadwal_cmp DROP FOREIGN KEY FK_D5FA6124AC3824A");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan DROP FOREIGN KEY FK_C8CEBE28AC3824A");
        $this->addSql("ALTER TABLE kepulangan_siswa DROP FOREIGN KEY FK_3632BDD5AC3824A");
        $this->addSql("ALTER TABLE wali_kelas DROP FOREIGN KEY FK_CE886CD9AC3824A");
        $this->addSql("ALTER TABLE kehadiran_siswa DROP FOREIGN KEY FK_79FA6E66850D8FD8");
        $this->addSql("ALTER TABLE pendidikan_siswa DROP FOREIGN KEY FK_A36A8D7A850D8FD8");
        $this->addSql("ALTER TABLE penyakit_siswa DROP FOREIGN KEY FK_526CBF72850D8FD8");
        $this->addSql("ALTER TABLE siswa_kelas DROP FOREIGN KEY FK_882A3907850D8FD8");
        $this->addSql("ALTER TABLE pembayaran_sekali DROP FOREIGN KEY FK_EB34A2F7850D8FD8");
        $this->addSql("ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479850D8FD8");
        $this->addSql("ALTER TABLE siswa_cmp DROP FOREIGN KEY FK_D6DFF747850D8FD8");
        $this->addSql("ALTER TABLE tugas_akhir DROP FOREIGN KEY FK_35829604850D8FD8");
        $this->addSql("ALTER TABLE kepulangan_siswa DROP FOREIGN KEY FK_3632BDD5850D8FD8");
        $this->addSql("ALTER TABLE orangtua_wali DROP FOREIGN KEY FK_258AE7B2850D8FD8");
        $this->addSql("ALTER TABLE pembayaran_rutin DROP FOREIGN KEY FK_692AC9CC850D8FD8");
        $this->addSql("ALTER TABLE panitia_pendaftaran DROP FOREIGN KEY FK_BB548D617949DAB");
        $this->addSql("ALTER TABLE biaya_rutin DROP FOREIGN KEY FK_F4EDECBC7949DAB");
        $this->addSql("ALTER TABLE imbalan_pendaftaran DROP FOREIGN KEY FK_13653D697949DAB");
        $this->addSql("ALTER TABLE biaya_sekali DROP FOREIGN KEY FK_BBAC14EE7949DAB");
        $this->addSql("ALTER TABLE calon_siswa DROP FOREIGN KEY FK_FD7929C67949DAB");
        $this->addSql("ALTER TABLE siswa DROP FOREIGN KEY FK_3202BD7D7949DAB");
        $this->addSql("ALTER TABLE kehadiran_siswa DROP FOREIGN KEY FK_79FA6E661EA2E8A7");
        $this->addSql("ALTER TABLE jadwal_kehadiran_kepulangan DROP FOREIGN KEY FK_C8CEBE281EA2E8A7");
        $this->addSql("ALTER TABLE kepulangan_siswa DROP FOREIGN KEY FK_3632BDD51EA2E8A7");
        $this->addSql("DROP TABLE cukil_mp");
        $this->addSql("DROP TABLE sms_kepulangan_siswa");
        $this->addSql("DROP TABLE kehadiran_siswa");
        $this->addSql("DROP TABLE pendidikan_siswa");
        $this->addSql("DROP TABLE referensi");
        $this->addSql("DROP TABLE siswahadir_jcmp");
        $this->addSql("DROP TABLE penyakit_siswa");
        $this->addSql("DROP TABLE jadwal_bel");
        $this->addSql("DROP TABLE panitia_pendaftaran");
        $this->addSql("DROP TABLE siswa_kelas");
        $this->addSql("DROP TABLE jenis_imbalan");
        $this->addSql("DROP TABLE jenjang");
        $this->addSql("DROP TABLE jadwal_sms");
        $this->addSql("DROP TABLE pembayaran_sekali");
        $this->addSql("DROP TABLE logfp");
        $this->addSql("DROP TABLE jadwal_cmp");
        $this->addSql("DROP TABLE kelompok_mp");
        $this->addSql("DROP TABLE calon_pembayaran_rutin");
        $this->addSql("DROP TABLE sekolah");
        $this->addSql("DROP TABLE tahun");
        $this->addSql("DROP TABLE biaya_rutin");
        $this->addSql("DROP TABLE imbalan_pendaftaran");
        $this->addSql("DROP TABLE fos_user");
        $this->addSql("DROP TABLE gelombang");
        $this->addSql("DROP TABLE templatesms");
        $this->addSql("DROP TABLE jenis_nilai");
        $this->addSql("DROP TABLE siswa_cmp");
        $this->addSql("DROP TABLE guruhadir");
        $this->addSql("DROP TABLE jadwal_kehadiran_kepulangan");
        $this->addSql("DROP TABLE tugas_akhir");
        $this->addSql("DROP TABLE biaya_sekali");
        $this->addSql("DROP TABLE logsms_masuk");
        $this->addSql("DROP TABLE kepulangan_siswa");
        $this->addSql("DROP TABLE mata_pelajaran");
        $this->addSql("DROP TABLE guru");
        $this->addSql("DROP TABLE calon_pembayaran_sekali");
        $this->addSql("DROP TABLE calon_siswa");
        $this->addSql("DROP TABLE kalender_pendidikan");
        $this->addSql("DROP TABLE logsms_keluar");
        $this->addSql("DROP TABLE kehadiran_guru");
        $this->addSql("DROP TABLE orangtua_wali");
        $this->addSql("DROP TABLE penjurusan");
        $this->addSql("DROP TABLE siswahadir");
        $this->addSql("DROP TABLE jenisbiaya");
        $this->addSql("DROP TABLE staf");
        $this->addSql("DROP TABLE semester");
        $this->addSql("DROP TABLE kelas");
        $this->addSql("DROP TABLE pendidikan_guru");
        $this->addSql("DROP TABLE guru_mp");
        $this->addSql("DROP TABLE pembayaran_rutin");
        $this->addSql("DROP TABLE siswa");
        $this->addSql("DROP TABLE tahunmasuk");
        $this->addSql("DROP TABLE guruhadir_jcmp");
        $this->addSql("DROP TABLE status_kehadiran_kepulangan");
        $this->addSql("DROP TABLE wali_kelas");
        $this->addSql("DROP TABLE mesin_kehadiran");

        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonpr`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonps`;");
        $this->addSql("DROP TRIGGER IF EXISTS `befins_calonsiswa`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertkelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeupdatekelas`;");
        $this->addSql("DROP TRIGGER IF EXISTS `beforeinsertsiswa`");
    }
}
