<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210102814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D79F37AE5');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DE00EE68D');
        $this->addSql('DROP INDEX IDX_6EEAA67D79F37AE5 ON commande');
        $this->addSql('DROP INDEX IDX_6EEAA67DE00EE68D ON commande');
        $this->addSql('ALTER TABLE commande ADD user_id INT NOT NULL, ADD product_id INT NOT NULL, DROP id_user_id, DROP id_product_id, CHANGE date_commande date_commande DATETIME NOT NULL, CHANGE total_amount total_amount DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DA76ED395 ON commande (user_id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D4584665A ON commande (product_id)');
        $this->addSql('ALTER TABLE product ADD picture VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D4584665A');
        $this->addSql('DROP INDEX IDX_6EEAA67DA76ED395 ON commande');
        $this->addSql('DROP INDEX IDX_6EEAA67D4584665A ON commande');
        $this->addSql('ALTER TABLE commande ADD id_user_id INT NOT NULL, ADD id_product_id INT NOT NULL, DROP user_id, DROP product_id, CHANGE date_commande date_commande DATE NOT NULL, CHANGE total_amount total_amount INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DE00EE68D FOREIGN KEY (id_product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D79F37AE5 ON commande (id_user_id)');
        $this->addSql('CREATE INDEX IDX_6EEAA67DE00EE68D ON commande (id_product_id)');
        $this->addSql('ALTER TABLE product DROP picture');
    }
}
