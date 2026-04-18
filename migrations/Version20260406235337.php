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
        // 1. Fix exercice table
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT AUTO_INCREMENT NOT NULL');
        
        // 2. Fix password_reset_tokens - ajouter la clé primaire auto-incrémentée
        $this->addSql('ALTER TABLE password_reset_tokens MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD PRIMARY KEY (id)');
        
        // 3. Ajouter la colonne user_id à session
        $this->addSql('ALTER TABLE session ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_SESSION_USER FOREIGN KEY (user_id) REFERENCES utilisateur(idU) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_SESSION_USER ON session (user_id)');
        
        // 4. Fix foreign key for password_reset_tokens (après avoir ajouté la clé primaire)
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_PASSWORD_RESET_TOKENS_USER FOREIGN KEY (user_id) REFERENCES utilisateur(idU) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_PASSWORD_RESET_TOKENS_USER');
        $this->addSql('ALTER TABLE password_reset_tokens DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE password_reset_tokens MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE exercice CHANGE id_ex id_ex INT NOT NULL');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_SESSION_USER');
        $this->addSql('DROP INDEX IDX_SESSION_USER ON session');
        $this->addSql('ALTER TABLE session DROP user_id');
    }
}
