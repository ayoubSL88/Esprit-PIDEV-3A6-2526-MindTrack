<?php

declare(strict_types=1);

namespace DoctrineMigrations;

    use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Finish synchronizing the database schema with the current entity mappings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exercice CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE progression CHANGE total_sessions total_sessions INT NOT NULL, CHANGE sessions_terminees sessions_terminees INT NOT NULL, CHANGE temps_total temps_total INT NOT NULL, CHANGE moyenne_score moyenne_score DOUBLE PRECISION DEFAULT NULL');

        $hasSessionUserId = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' AND COLUMN_NAME = 'user_id'") > 0;
        if (!$hasSessionUserId) {
            $this->addSql('ALTER TABLE session ADD user_id INT DEFAULT NULL');
        }

        $hasSessionSteps = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' AND COLUMN_NAME = 'steps'") > 0;
        if (!$hasSessionSteps) {
            $this->addSql('ALTER TABLE session ADD steps JSON DEFAULT NULL');
        }

        $this->addSql('UPDATE session SET user_id = 1 WHERE user_id IS NULL OR user_id = 0');
        $this->addSql('ALTER TABLE session CHANGE resultat resultat VARCHAR(255) DEFAULT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL, CHANGE steps steps JSON DEFAULT NULL');

        $sessionUserIndexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' AND INDEX_NAME = 'IDX_D044D5D4A76ED395'") > 0;
        if (!$sessionUserIndexExists) {
            $this->addSql('CREATE INDEX IDX_D044D5D4A76ED395 ON session (user_id)');
        }

        $sessionUserFkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'session' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'FK_D044D5D4A76ED395'") > 0;
        if (!$sessionUserFkExists) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u)');
        }

        $utilisateurIdAutoIncrement = $this->connection->fetchOne("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'utilisateur' AND COLUMN_NAME = 'id_u'");
        if ($utilisateurIdAutoIncrement !== 'auto_increment') {
            $this->addSql('ALTER TABLE utilisateur CHANGE id_u id_u INT AUTO_INCREMENT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur CHANGE id_u id_u INT NOT NULL');

        $this->addSql('DROP INDEX IDX_D044D5D4A76ED395 ON session');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4A76ED395');
        $this->addSql('ALTER TABLE session CHANGE resultat resultat VARCHAR(255) DEFAULT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL, CHANGE steps steps LONGTEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE progression CHANGE total_sessions total_sessions INT NOT NULL DEFAULT 0, CHANGE sessions_terminees sessions_terminees INT NOT NULL DEFAULT 0, CHANGE temps_total temps_total INT NOT NULL DEFAULT 0, CHANGE moyenne_score moyenne_score DOUBLE PRECISION DEFAULT NULL');

        $this->addSql('ALTER TABLE exercice CHANGE date_creation date_creation DATETIME DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL');
    }
}
