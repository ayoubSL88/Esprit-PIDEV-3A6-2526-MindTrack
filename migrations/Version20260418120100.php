<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create progression table without foreign keys first';
    }

    public function up(Schema $schema): void
    {
        // Supprimer l'ancienne table si elle existe
        $this->addSql('DROP TABLE IF EXISTS progression');
        
        // Créer la table SANS clés étrangères
        $this->addSql('CREATE TABLE progression (
            id_progression INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            exercice_id INT DEFAULT NULL,
            total_sessions INT NOT NULL DEFAULT 0,
            sessions_terminees INT NOT NULL DEFAULT 0,
            temps_total INT NOT NULL DEFAULT 0,
            moyenne_score DOUBLE PRECISION DEFAULT NULL,
            derniere_activite DATETIME NOT NULL,
            PRIMARY KEY(id_progression)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS progression');
    }
}