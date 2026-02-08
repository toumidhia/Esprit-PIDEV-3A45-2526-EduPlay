<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add missing columns to seance table: title, date, location, status, description
 */
final class Version20260208000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title, date, location, status, and description columns to seance table';
    }

    public function up(Schema $schema): void
    {
        // Add new columns to seance table
        $this->addSql('ALTER TABLE seance ADD title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE seance ADD date DATE NOT NULL');
        $this->addSql('ALTER TABLE seance ADD location VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE seance ADD status VARCHAR(50) NOT NULL DEFAULT "scheduled"');
        $this->addSql('ALTER TABLE seance ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove columns from seance table
        $this->addSql('ALTER TABLE seance DROP title');
        $this->addSql('ALTER TABLE seance DROP date');
        $this->addSql('ALTER TABLE seance DROP location');
        $this->addSql('ALTER TABLE seance DROP status');
        $this->addSql('ALTER TABLE seance DROP description');
    }
}
