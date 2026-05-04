<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make required FK columns non-nullable to match orphanRemoval relations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo CHANGE exercice_id exercice_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE rappel_habitude CHANGE habitude_id habitude_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE suivihabitude CHANGE habitude_id habitude_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE jalonprogression CHANGE objectif_id objectif_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE planaction CHANGE objectif_id objectif_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE planificateurintelligent CHANGE objectif_id objectif_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE user_id user_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE profilpsychologique CHANGE user_id user_id INT(11) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profilpsychologique CHANGE user_id user_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE user_id user_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planificateurintelligent CHANGE objectif_id objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planaction CHANGE objectif_id objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE jalonprogression CHANGE objectif_id objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE suivihabitude CHANGE habitude_id habitude_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE rappel_habitude CHANGE habitude_id habitude_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE todo CHANGE exercice_id exercice_id INT(11) DEFAULT NULL');
    }
}
