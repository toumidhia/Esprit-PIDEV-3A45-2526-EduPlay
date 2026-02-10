<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Create subscription table
 */
final class Version20260209120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create subscription table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, parent_id_id INT NOT NULL, kid_id_id INT NOT NULL, course_id_id INT NOT NULL, subscribed_at DATETIME NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY(id), INDEX IDX_A3C664D3727ACA70 (parent_id_id), INDEX IDX_A3C664D335EDB40 (kid_id_id), INDEX IDX_A3C664D396EF99BF (course_id_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3727ACA70 FOREIGN KEY (parent_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D335EDB40 FOREIGN KEY (kid_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D396EF99BF FOREIGN KEY (course_id_id) REFERENCES course (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3727ACA70');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D335EDB40');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D396EF99BF');
        $this->addSql('DROP TABLE subscription');
    }
}
