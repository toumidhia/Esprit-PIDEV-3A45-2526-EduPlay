<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205193235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD username VARCHAR(100) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD telephone VARCHAR(20) DEFAULT NULL, ADD adresse VARCHAR(255) DEFAULT NULL, ADD specialite VARCHAR(100) DEFAULT NULL, ADD niveau VARCHAR(50) DEFAULT NULL, ADD parent_id INT DEFAULT NULL, CHANGE birth_date birth_date DATE DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE role roles JSON NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('CREATE INDEX IDX_8D93D649727ACA70 ON user (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON user');
        $this->addSql('DROP INDEX IDX_8D93D649727ACA70 ON user');
        $this->addSql('ALTER TABLE user DROP username, DROP created_at, DROP telephone, DROP adresse, DROP specialite, DROP niveau, DROP parent_id, CHANGE birth_date birth_date DATE NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE roles role JSON NOT NULL');
    }
}
