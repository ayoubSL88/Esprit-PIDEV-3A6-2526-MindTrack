<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_sent_at to habit reminders to prevent duplicate sends within the same minute.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rappel_habitude ADD last_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rappel_habitude DROP last_sent_at');
    }
}
