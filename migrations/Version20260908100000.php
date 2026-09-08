<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align the session exercise foreign key column with the Session entity mapping.';
    }

    public function up(Schema $schema): void
    {
        $column = $this->connection->fetchAssociative(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
             AND COLUMN_NAME IN ('exercice_id', 'idEx')
             ORDER BY FIELD(COLUMN_NAME, 'exercice_id', 'idEx') LIMIT 1"
        );

        if ($column === false) {
            throw new \RuntimeException('The session table has neither exercice_id nor idEx.');
        }

        $columnName = (string) $column['COLUMN_NAME'];

        if ($columnName === 'exercice_id') {
            $foreignKey = $this->connection->fetchOne(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
                 AND COLUMN_NAME = 'exercice_id' AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );

            if ($foreignKey !== false) {
                $this->addSql(sprintf('ALTER TABLE session DROP FOREIGN KEY `%s`', $foreignKey));
            }

            $index = $this->connection->fetchOne(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
                 AND COLUMN_NAME = 'exercice_id' AND INDEX_NAME <> 'PRIMARY'
                 LIMIT 1"
            );

            if ($index !== false) {
                $this->addSql(sprintf('DROP INDEX `%s` ON session', $index));
            }

            $this->addSql('ALTER TABLE session CHANGE exercice_id idEx INT NOT NULL');
        }

        $hasForeignKey = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
             AND COLUMN_NAME = 'idEx' AND REFERENCED_TABLE_NAME = 'exercice'"
        ) > 0;

        if (!$hasForeignKey) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4FB644627 FOREIGN KEY (idEx) REFERENCES exercice (id_ex) ON DELETE CASCADE');
        }

        $hasIndex = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
             AND COLUMN_NAME = 'idEx' AND INDEX_NAME <> 'PRIMARY'"
        ) > 0;

        if (!$hasIndex) {
            $this->addSql('CREATE INDEX IDX_D044D5D4FB644627 ON session (idEx)');
        }
    }

    public function down(Schema $schema): void
    {
        $foreignKey = $this->connection->fetchOne(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
             AND COLUMN_NAME = 'idEx' AND REFERENCED_TABLE_NAME = 'exercice'
             LIMIT 1"
        );

        if ($foreignKey !== false) {
            $this->addSql(sprintf('ALTER TABLE session DROP FOREIGN KEY `%s`', $foreignKey));
        }

        $index = $this->connection->fetchOne(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session'
             AND COLUMN_NAME = 'idEx' AND INDEX_NAME <> 'PRIMARY'
             LIMIT 1"
        );

        if ($index !== false) {
            $this->addSql(sprintf('DROP INDEX `%s` ON session', $index));
        }

        $this->addSql('ALTER TABLE session CHANGE idEx exercice_id INT NOT NULL');
    }
}