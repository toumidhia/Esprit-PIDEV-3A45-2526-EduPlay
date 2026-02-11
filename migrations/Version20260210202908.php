<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210202908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_registration ADD parent_phone VARCHAR(30) DEFAULT NULL, ADD child_class_level VARCHAR(80) DEFAULT NULL, ADD medical_notes LONGTEXT DEFAULT NULL, ADD emergency_contact_name VARCHAR(120) DEFAULT NULL, ADD emergency_contact_phone VARCHAR(30) DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL, CHANGE status status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_registration DROP parent_phone, DROP child_class_level, DROP medical_notes, DROP emergency_contact_name, DROP emergency_contact_phone, DROP notes, CHANGE status status VARCHAR(30) NOT NULL');
    }
}
