<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421042248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT NOT NULL');
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY `FK_AA5D0867A2D72265`');
        $this->addSql('DROP INDEX IDX_10DD3E5FA2D72265 ON habitude');
        $this->addSql('ALTER TABLE habitude DROP idU');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE habitude ADD idU INT DEFAULT NULL');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT `FK_AA5D0867A2D72265` FOREIGN KEY (idU) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_10DD3E5FA2D72265 ON habitude (idU)');
    }
}
