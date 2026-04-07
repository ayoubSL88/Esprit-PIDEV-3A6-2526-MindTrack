<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406235337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable auto-generated identifiers for exercice records.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT AUTO_INCREMENT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT NOT NULL');
    }
}
