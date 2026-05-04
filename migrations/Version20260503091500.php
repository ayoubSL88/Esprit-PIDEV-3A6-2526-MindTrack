<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename FK columns to snake_case _id suffix for consistency.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4FB644627');
        $this->addSql('ALTER TABLE session CHANGE idEx exercice_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_SESSION_EXERCICE_ID FOREIGN KEY (exercice_id) REFERENCES exercice (id_ex) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY FK_5A0EB6A0B7BBED16');
        $this->addSql('ALTER TABLE todo CHANGE idExercice exercice_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT FK_TODO_EXERCICE_ID FOREIGN KEY (exercice_id) REFERENCES exercice (id_ex) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE rappel_habitude DROP FOREIGN KEY FK_C4294AE0437E1404');
        $this->addSql('ALTER TABLE rappel_habitude CHANGE idHabitude habitude_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE rappel_habitude ADD CONSTRAINT FK_RAPPEL_HABITUDE_ID FOREIGN KEY (habitude_id) REFERENCES habitude (id_habitude) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE suivihabitude DROP FOREIGN KEY FK_E1522B87437E1404');
        $this->addSql('ALTER TABLE suivihabitude CHANGE idHabitude habitude_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE suivihabitude ADD CONSTRAINT FK_SUIVI_HABITUDE_ID FOREIGN KEY (habitude_id) REFERENCES habitude (id_habitude) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE jalonprogression DROP FOREIGN KEY FK_2A71AD13672EC9EB');
        $this->addSql('ALTER TABLE jalonprogression CHANGE idObj objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE jalonprogression ADD CONSTRAINT FK_JALONPROGRESSION_OBJECTIF_ID FOREIGN KEY (objectif_id) REFERENCES objectif (id_obj) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE planaction DROP FOREIGN KEY FK_9E5225F2672EC9EB');
        $this->addSql('ALTER TABLE planaction CHANGE idObj objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planaction ADD CONSTRAINT FK_PLANACTION_OBJECTIF_ID FOREIGN KEY (objectif_id) REFERENCES objectif (id_obj) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE planificateurintelligent DROP FOREIGN KEY FK_B6FD5C8B672EC9EB');
        $this->addSql('ALTER TABLE planificateurintelligent CHANGE idObj objectif_id INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planificateurintelligent ADD CONSTRAINT FK_PLANIFICATEURINTELLIGENT_OBJECTIF_ID FOREIGN KEY (objectif_id) REFERENCES objectif (id_obj) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planificateurintelligent DROP FOREIGN KEY FK_PLANIFICATEURINTELLIGENT_OBJECTIF_ID');
        $this->addSql('ALTER TABLE planificateurintelligent CHANGE objectif_id idObj INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planificateurintelligent ADD CONSTRAINT FK_B6FD5C8B672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE planaction DROP FOREIGN KEY FK_PLANACTION_OBJECTIF_ID');
        $this->addSql('ALTER TABLE planaction CHANGE objectif_id idObj INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE planaction ADD CONSTRAINT FK_9E5225F2672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE jalonprogression DROP FOREIGN KEY FK_JALONPROGRESSION_OBJECTIF_ID');
        $this->addSql('ALTER TABLE jalonprogression CHANGE objectif_id idObj INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE jalonprogression ADD CONSTRAINT FK_2A71AD13672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE suivihabitude DROP FOREIGN KEY FK_SUIVI_HABITUDE_ID');
        $this->addSql('ALTER TABLE suivihabitude CHANGE habitude_id idHabitude INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE suivihabitude ADD CONSTRAINT FK_E1522B87437E1404 FOREIGN KEY (idHabitude) REFERENCES habitude (id_habitude) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE rappel_habitude DROP FOREIGN KEY FK_RAPPEL_HABITUDE_ID');
        $this->addSql('ALTER TABLE rappel_habitude CHANGE habitude_id idHabitude INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE rappel_habitude ADD CONSTRAINT FK_C4294AE0437E1404 FOREIGN KEY (idHabitude) REFERENCES habitude (id_habitude) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY FK_TODO_EXERCICE_ID');
        $this->addSql('ALTER TABLE todo CHANGE exercice_id idExercice INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT FK_5A0EB6A0B7BBED16 FOREIGN KEY (idExercice) REFERENCES exercice (id_ex) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_SESSION_EXERCICE_ID');
        $this->addSql('ALTER TABLE session CHANGE exercice_id idEx INT(11) NOT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4FB644627 FOREIGN KEY (idEx) REFERENCES exercice (id_ex) ON DELETE CASCADE');
    }
}
