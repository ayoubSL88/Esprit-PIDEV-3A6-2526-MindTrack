<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add richer profile metadata fields to utilisateur for trust score and profile customization.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD phone_number VARCHAR(30) DEFAULT NULL, ADD city VARCHAR(120) DEFAULT NULL, ADD country VARCHAR(120) DEFAULT NULL, ADD timezone VARCHAR(80) DEFAULT NULL, ADD occupation VARCHAR(160) DEFAULT NULL, ADD biography LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP phone_number, DROP city, DROP country, DROP timezone, DROP occupation, DROP biography');
    }
}
