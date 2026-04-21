<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add screenshot and audio attachment paths to journalemotionnel.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journalemotionnel ADD screenshot_path VARCHAR(255) DEFAULT NULL, ADD audio_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE journalemotionnel DROP screenshot_path, DROP audio_path');
    }
}
