<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409230141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book_request (id INT AUTO_INCREMENT NOT NULL, enfant_id INT NOT NULL, resource_id INT DEFAULT NULL, book_title VARCHAR(255) NOT NULL, is_notified TINYINT(1) NOT NULL, is_available TINYINT(1) NOT NULL, requested_at DATETIME NOT NULL, notified_at DATETIME DEFAULT NULL, INDEX IDX_A8B7A709450D2529 (enfant_id), INDEX IDX_A8B7A70989329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, date_commande DATETIME NOT NULL, total_amount DOUBLE PRECISION NOT NULL, stripe_payment_id VARCHAR(255) DEFAULT NULL, is_paid TINYINT(1) NOT NULL, INDEX IDX_6EEAA67DA76ED395 (user_id), INDEX IDX_6EEAA67D4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, teacher_id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, duration_training VARCHAR(255) NOT NULL, level VARCHAR(255) NOT NULL, pdf_file VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_169E6FB941807E1D (teacher_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_registration (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, parent_id INT NOT NULL, status VARCHAR(255) NOT NULL, registered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', child_full_name VARCHAR(120) NOT NULL, parent_phone VARCHAR(30) DEFAULT NULL, child_class_level VARCHAR(80) DEFAULT NULL, medical_notes LONGTEXT DEFAULT NULL, emergency_contact_name VARCHAR(120) DEFAULT NULL, emergency_contact_phone VARCHAR(30) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_8FBBAD5471F7E88B (event_id), INDEX IDX_8FBBAD54727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_resource (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, context LONGTEXT DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FA7D1DC671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, id_level_id INT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_232B318CF6AA732 (id_level_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, difficulty INT NOT NULL, min_age INT NOT NULL, max_age INT NOT NULL, pedag_goal VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE library (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(20) NOT NULL, description VARCHAR(100) DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, min_age INT NOT NULL, max_age INT NOT NULL, level VARCHAR(20) NOT NULL, theme VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, availability TINYINT(1) NOT NULL, picture VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, library_id_id INT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, summary LONGTEXT DEFAULT NULL, cover_image VARCHAR(255) DEFAULT NULL, pdf_file VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, min_age INT NOT NULL, max_age INT NOT NULL, language VARCHAR(255) NOT NULL, INDEX IDX_BC91F4164CE14C78 (library_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resource_access (id INT AUTO_INCREMENT NOT NULL, resource_id_id INT NOT NULL, child_id_id INT DEFAULT NULL, open_count INT DEFAULT NULL, is_completed TINYINT(1) DEFAULT NULL, INDEX IDX_CE95C1AE54FFE465 (resource_id_id), INDEX IDX_CE95C1AE2C423CC4 (child_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE school_event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, image_path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, course_id INT NOT NULL, title VARCHAR(255) DEFAULT NULL, date DATE DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_DF7DFD0E591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, parent_id INT NOT NULL, kid_id INT NOT NULL, course_id INT NOT NULL, subscribed_at DATETIME NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_A3C664D3727ACA70 (parent_id), INDEX IDX_A3C664D36A973770 (kid_id), INDEX IDX_A3C664D3591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birth_date DATE DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, username VARCHAR(100) DEFAULT NULL, password VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, roles JSON NOT NULL, active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, telephone VARCHAR(20) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, specialite VARCHAR(100) DEFAULT NULL, niveau VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), INDEX IDX_8D93D649727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book_request ADD CONSTRAINT FK_A8B7A709450D2529 FOREIGN KEY (enfant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE book_request ADD CONSTRAINT FK_A8B7A70989329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB941807E1D FOREIGN KEY (teacher_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES school_event (id)');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD54727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_resource ADD CONSTRAINT FK_FA7D1DC671F7E88B FOREIGN KEY (event_id) REFERENCES school_event (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF6AA732 FOREIGN KEY (id_level_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4164CE14C78 FOREIGN KEY (library_id_id) REFERENCES library (id)');
        $this->addSql('ALTER TABLE resource_access ADD CONSTRAINT FK_CE95C1AE54FFE465 FOREIGN KEY (resource_id_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE resource_access ADD CONSTRAINT FK_CE95C1AE2C423CC4 FOREIGN KEY (child_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D36A973770 FOREIGN KEY (kid_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_request DROP FOREIGN KEY FK_A8B7A709450D2529');
        $this->addSql('ALTER TABLE book_request DROP FOREIGN KEY FK_A8B7A70989329D25');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D4584665A');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB941807E1D');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD5471F7E88B');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD54727ACA70');
        $this->addSql('ALTER TABLE event_resource DROP FOREIGN KEY FK_FA7D1DC671F7E88B');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CF6AA732');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4164CE14C78');
        $this->addSql('ALTER TABLE resource_access DROP FOREIGN KEY FK_CE95C1AE54FFE465');
        $this->addSql('ALTER TABLE resource_access DROP FOREIGN KEY FK_CE95C1AE2C423CC4');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E591CC992');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3727ACA70');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D36A973770');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3591CC992');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649727ACA70');
        $this->addSql('DROP TABLE book_request');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE event_registration');
        $this->addSql('DROP TABLE event_resource');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE level');
        $this->addSql('DROP TABLE library');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE resource_access');
        $this->addSql('DROP TABLE school_event');
        $this->addSql('DROP TABLE seance');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE user');
    }
}
