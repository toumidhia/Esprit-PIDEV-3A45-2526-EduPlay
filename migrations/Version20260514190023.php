<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260514190023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE flyway_schema_history');
        $this->addSql('ALTER TABLE commande DROP created_at, DROP parent_id, CHANGE total_amount total_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE school_event ADD capacity INT DEFAULT NULL, DROP max_capacity, DROP current_registrations');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('ALTER TABLE user DROP profile_picture, CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE flyway_schema_history (installed_rank INT NOT NULL, version VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, description VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, script VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, checksum INT DEFAULT NULL, installed_by VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, installed_on DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, execution_time INT NOT NULL, success TINYINT(1) NOT NULL, INDEX flyway_schema_history_s_idx (success), PRIMARY KEY(installed_rank)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE commande ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD parent_id INT DEFAULT NULL, CHANGE total_amount total_amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE price price DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE school_event ADD current_registrations INT DEFAULT NULL, CHANGE capacity max_capacity INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('ALTER TABLE user ADD profile_picture VARCHAR(255) DEFAULT NULL, CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
