<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203202815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, date_commande DATE NOT NULL, total_amount INT NOT NULL, id_user_id INT NOT NULL, id_product_id INT NOT NULL, INDEX IDX_6EEAA67D79F37AE5 (id_user_id), INDEX IDX_6EEAA67DE00EE68D (id_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, duration_training VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, level VARCHAR(255) NOT NULL, pdf_file VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, teacher_id_id INT NOT NULL, INDEX IDX_169E6FB92EBB220A (teacher_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE event_registration (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL, registered_at DATETIME NOT NULL, event_id INT NOT NULL, parent_id INT NOT NULL, INDEX IDX_8FBBAD5471F7E88B (event_id), INDEX IDX_8FBBAD54727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE event_resource (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, context LONGTEXT NOT NULL, file_path VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, event_id INT NOT NULL, INDEX IDX_FA7D1DC671F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, id_level_id INT NOT NULL, INDEX IDX_232B318CF6AA732 (id_level_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE level (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, difficulty INT NOT NULL, min_age INT NOT NULL, max_age INT NOT NULL, pedag_goal VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE library (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, cover_image VARCHAR(255) NOT NULL, min_age INT NOT NULL, max_age INT NOT NULL, level VARCHAR(255) NOT NULL, theme VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, availability TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, summary LONGTEXT NOT NULL, cover_image VARCHAR(255) NOT NULL, pdf_file VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, min_age INT NOT NULL, max_age INT NOT NULL, language VARCHAR(255) NOT NULL, library_id_id INT NOT NULL, INDEX IDX_BC91F4164CE14C78 (library_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource_access (id INT AUTO_INCREMENT NOT NULL, open_count INT DEFAULT NULL, is_completed TINYINT(1) DEFAULT NULL, resource_id_id INT NOT NULL, child_id_id INT DEFAULT NULL, INDEX IDX_CE95C1AE54FFE465 (resource_id_id), INDEX IDX_CE95C1AE2C423CC4 (child_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE school_event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, location VARCHAR(255) NOT NULL, image_path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE seance (id INT AUTO_INCREMENT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, course_id_id INT NOT NULL, INDEX IDX_DF7DFD0E96EF99BF (course_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, role JSON NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DE00EE68D FOREIGN KEY (id_product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB92EBB220A FOREIGN KEY (teacher_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD5471F7E88B FOREIGN KEY (event_id) REFERENCES school_event (id)');
        $this->addSql('ALTER TABLE event_registration ADD CONSTRAINT FK_8FBBAD54727ACA70 FOREIGN KEY (parent_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_resource ADD CONSTRAINT FK_FA7D1DC671F7E88B FOREIGN KEY (event_id) REFERENCES school_event (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF6AA732 FOREIGN KEY (id_level_id) REFERENCES level (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4164CE14C78 FOREIGN KEY (library_id_id) REFERENCES library (id)');
        $this->addSql('ALTER TABLE resource_access ADD CONSTRAINT FK_CE95C1AE54FFE465 FOREIGN KEY (resource_id_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE resource_access ADD CONSTRAINT FK_CE95C1AE2C423CC4 FOREIGN KEY (child_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE seance ADD CONSTRAINT FK_DF7DFD0E96EF99BF FOREIGN KEY (course_id_id) REFERENCES course (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D79F37AE5');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DE00EE68D');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB92EBB220A');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD5471F7E88B');
        $this->addSql('ALTER TABLE event_registration DROP FOREIGN KEY FK_8FBBAD54727ACA70');
        $this->addSql('ALTER TABLE event_resource DROP FOREIGN KEY FK_FA7D1DC671F7E88B');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CF6AA732');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4164CE14C78');
        $this->addSql('ALTER TABLE resource_access DROP FOREIGN KEY FK_CE95C1AE54FFE465');
        $this->addSql('ALTER TABLE resource_access DROP FOREIGN KEY FK_CE95C1AE2C423CC4');
        $this->addSql('ALTER TABLE seance DROP FOREIGN KEY FK_DF7DFD0E96EF99BF');
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
        $this->addSql('DROP TABLE user');
    }
}
