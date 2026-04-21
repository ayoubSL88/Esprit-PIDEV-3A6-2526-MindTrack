<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418120200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign keys to progression table';
    }

    public function up(Schema $schema): void
    {
        // Vérifier d'abord les types des colonnes
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        
        // Ajouter les clés étrangères
        $this->addSql('ALTER TABLE progression ADD INDEX IDX_PROGRESSION_USER (user_id)');
        $this->addSql('ALTER TABLE progression ADD INDEX IDX_PROGRESSION_EXERCICE (exercice_id)');
        
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE progression DROP INDEX IDX_PROGRESSION_USER');
        $this->addSql('ALTER TABLE progression DROP INDEX IDX_PROGRESSION_EXERCICE');
    }
}