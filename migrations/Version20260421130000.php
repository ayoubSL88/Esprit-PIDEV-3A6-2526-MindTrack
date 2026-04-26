<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure habitude owner foreign key column exists with normalized name id_u.';
    }

    public function up(Schema $schema): void
    {
        $hasIdU = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND COLUMN_NAME = 'idU'") > 0;
        $hasIdLower = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND COLUMN_NAME = 'id_u'") > 0;

        if ($hasIdU && !$hasIdLower) {
            $this->addSql('ALTER TABLE habitude CHANGE idU id_u INT DEFAULT NULL');
            $hasIdLower = true;
        }

        if (!$hasIdLower) {
            $this->addSql('ALTER TABLE habitude ADD id_u INT DEFAULT NULL');
        }

        $fkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'FK_HABITUDE_USER_ID_U'") > 0;
        if (!$fkExists) {
            $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_HABITUDE_USER_ID_U FOREIGN KEY (id_u) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
        }

        $indexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_HABITUDE_USER_ID_U'") > 0;
        if (!$indexExists) {
            $this->addSql('CREATE INDEX IDX_HABITUDE_USER_ID_U ON habitude (id_u)');
        }
    }

    public function down(Schema $schema): void
    {
        $fkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'FK_HABITUDE_USER_ID_U'") > 0;
        if ($fkExists) {
            $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_HABITUDE_USER_ID_U');
        }

        $indexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_HABITUDE_USER_ID_U'") > 0;
        if ($indexExists) {
            $this->addSql('DROP INDEX IDX_HABITUDE_USER_ID_U ON habitude');
        }

        $hasIdLower = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND COLUMN_NAME = 'id_u'") > 0;
        if ($hasIdLower) {
            $this->addSql('ALTER TABLE habitude DROP COLUMN id_u');
        }
    }
}