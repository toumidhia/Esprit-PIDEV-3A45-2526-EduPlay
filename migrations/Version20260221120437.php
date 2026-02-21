<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221120437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book_request (id INT AUTO_INCREMENT NOT NULL, enfant_id INT NOT NULL, resource_id INT DEFAULT NULL, book_title VARCHAR(255) NOT NULL, is_notified TINYINT(1) NOT NULL, is_available TINYINT(1) NOT NULL, requested_at DATETIME NOT NULL, notified_at DATETIME DEFAULT NULL, INDEX IDX_A8B7A709450D2529 (enfant_id), INDEX IDX_A8B7A70989329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book_request ADD CONSTRAINT FK_A8B7A709450D2529 FOREIGN KEY (enfant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE book_request ADD CONSTRAINT FK_A8B7A70989329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD stripe_payment_id VARCHAR(255) DEFAULT NULL, ADD is_paid TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_request DROP FOREIGN KEY FK_A8B7A709450D2529');
        $this->addSql('ALTER TABLE book_request DROP FOREIGN KEY FK_A8B7A70989329D25');
        $this->addSql('DROP TABLE book_request');
        $this->addSql('ALTER TABLE commande DROP stripe_payment_id, DROP is_paid');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id)');
    }
}
