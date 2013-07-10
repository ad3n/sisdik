<?php

namespace Application\Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration, Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Versiontriggers extends AbstractMigration
{
    public function up(Schema $schema) {
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

