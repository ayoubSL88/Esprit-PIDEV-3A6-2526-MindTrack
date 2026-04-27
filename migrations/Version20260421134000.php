<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change journal note_personnelle column from VARCHAR to TEXT for TinyMCE rich content.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journalemotionnel MODIFY note_personnelle TEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journalemotionnel MODIFY note_personnelle VARCHAR(255) NOT NULL');
    }
}
