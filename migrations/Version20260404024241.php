<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404024241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE badge_progression CHANGE id id INT NOT NULL, CHANGE exercices_completes exercices_completes INT NOT NULL');
        $this->addSql('ALTER TABLE exercice MODIFY idEx INT NOT NULL');
        $this->addSql('ALTER TABLE exercice ADD id_ex INT NOT NULL, DROP idEx, CHANGE difficulte difficulte VARCHAR(50) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE demarche demarche LONGTEXT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE date_modification date_modification DATETIME NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_ex)');
        $this->addSql('ALTER TABLE habitude MODIFY idHabitude INT NOT NULL');
        $this->addSql('ALTER TABLE habitude ADD id_habitude INT NOT NULL, ADD habit_type VARCHAR(10) NOT NULL, ADD target_value INT NOT NULL, DROP idHabitude, DROP habitType, DROP targetValue, CHANGE unit unit VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_habitude)');
        $this->addSql('DROP INDEX idx_humeur_date ON humeur');
        $this->addSql('ALTER TABLE humeur MODIFY idH INT NOT NULL');
        $this->addSql('ALTER TABLE humeur ADD id_h INT NOT NULL, DROP idH, CHANGE TypeHumeur type_humeur VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_h)');
        $this->addSql('ALTER TABLE jalonprogression DROP FOREIGN KEY `fk_jalon_obj`');
        $this->addSql('ALTER TABLE jalonprogression MODIFY idJalon INT NOT NULL');
        $this->addSql('ALTER TABLE jalonprogression ADD id_jalon INT NOT NULL, ADD date_atteinte DATE NOT NULL, ADD pourcentage_progression INT NOT NULL, DROP idJalon, DROP dateAtteinte, DROP pourcentageProgression, CHANGE idObj idObj INT DEFAULT NULL, CHANGE atteint atteint TINYINT NOT NULL, CHANGE dateCible date_cible DATE NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_jalon)');
        $this->addSql('ALTER TABLE jalonprogression ADD CONSTRAINT FK_2A71AD13672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jalonprogression RENAME INDEX idx_jalon_obj TO IDX_2A71AD13672EC9EB');
        $this->addSql('DROP INDEX idx_journal_date ON journalemotionnel');
        $this->addSql('ALTER TABLE journalemotionnel MODIFY idJ INT NOT NULL');
        $this->addSql('ALTER TABLE journalemotionnel ADD id_j INT NOT NULL, ADD date_creation DATETIME NOT NULL, DROP idJ, DROP dateCreation, CHANGE NotePersonnelle note_personnelle VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_j)');
        $this->addSql('DROP INDEX idx_objectif_dates ON objectif');
        $this->addSql('ALTER TABLE objectif MODIFY idObj INT NOT NULL');
        $this->addSql('ALTER TABLE objectif ADD id_obj INT NOT NULL, ADD date_debut DATE NOT NULL, ADD date_fin DATE NOT NULL, DROP idObj, DROP dateDebut, DROP dateFin, CHANGE descriprion descriprion VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_obj)');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY `fk_password_reset_user`');
        $this->addSql('DROP INDEX idx_password_reset_user_created ON password_reset_tokens');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE used used TINYINT NOT NULL, CHANGE attempts attempts INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planaction DROP FOREIGN KEY `fk_plan_objectif`');
        $this->addSql('ALTER TABLE planaction MODIFY idPlan INT NOT NULL');
        $this->addSql('ALTER TABLE planaction ADD id_plan INT NOT NULL, DROP idPlan, CHANGE idObj idObj INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_plan)');
        $this->addSql('ALTER TABLE planaction ADD CONSTRAINT FK_9E5225F2672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planaction RENAME INDEX idx_plan_objectif TO IDX_9E5225F2672EC9EB');
        $this->addSql('ALTER TABLE planificateurintelligent DROP INDEX uk_planif_obj, ADD INDEX IDX_B6FD5C8B672EC9EB (idObj)');
        $this->addSql('ALTER TABLE planificateurintelligent DROP FOREIGN KEY `fk_planif_obj`');
        $this->addSql('ALTER TABLE planificateurintelligent MODIFY idPlanificateur INT NOT NULL');
        $this->addSql('ALTER TABLE planificateurintelligent ADD id_planificateur INT NOT NULL, ADD mode_organisation VARCHAR(30) NOT NULL, ADD capacite_quotidienne INT NOT NULL, ADD derniere_generation DATETIME NOT NULL, DROP idPlanificateur, DROP modeOrganisation, DROP capaciteQuotidienne, DROP derniereGeneration, CHANGE idObj idObj INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_planificateur)');
        $this->addSql('ALTER TABLE planificateurintelligent ADD CONSTRAINT FK_B6FD5C8B672EC9EB FOREIGN KEY (idObj) REFERENCES objectif (id_obj) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profilpsychologique DROP INDEX uk_profil_user, ADD INDEX IDX_796DA445A2D72265 (idU)');
        $this->addSql('ALTER TABLE profilpsychologique DROP FOREIGN KEY `fk_profil_user`');
        $this->addSql('ALTER TABLE profilpsychologique MODIFY idP INT NOT NULL');
        $this->addSql('ALTER TABLE profilpsychologique ADD id_p INT NOT NULL, ADD niveau_stress INT NOT NULL, ADD niveau_motivation INT NOT NULL, DROP idP, DROP NiveauStress, DROP NiveauMotivation, CHANGE Description description VARCHAR(255) NOT NULL, CHANGE idU idU INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_p)');
        $this->addSql('ALTER TABLE profilpsychologique ADD CONSTRAINT FK_796DA445A2D72265 FOREIGN KEY (idU) REFERENCES utilisateur (id_u) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression MODIFY idProgression INT NOT NULL');
        $this->addSql('ALTER TABLE progression ADD id_progression INT NOT NULL, ADD id_jalon INT NOT NULL, ADD date_realisation DATETIME NOT NULL, ADD score_obtenu INT NOT NULL, ADD ressenti_utilisateur INT NOT NULL, ADD notes_personnelles LONGTEXT NOT NULL, ADD temps_passe INT NOT NULL, ADD id_ex INT NOT NULL, ADD id_session INT NOT NULL, ADD date_atteinte DATETIME NOT NULL, ADD pourcentage_progression INT NOT NULL, DROP idProgression, DROP idJalon, DROP dateRealisation, DROP scoreObtenu, DROP ressentiUtilisateur, DROP notesPersonnelles, DROP tempsPasse, DROP idEx, DROP idSession, DROP dateAtteinte, DROP pourcentageProgression, CHANGE atteint atteint TINYINT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_progression)');
        $this->addSql('ALTER TABLE rappel_habitude DROP FOREIGN KEY `fk_rappel_habitude`');
        $this->addSql('DROP INDEX idx_rappel_actif_heure ON rappel_habitude');
        $this->addSql('ALTER TABLE rappel_habitude MODIFY idRappel INT NOT NULL');
        $this->addSql('ALTER TABLE rappel_habitude ADD id_rappel INT NOT NULL, ADD heure_rappel VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL, ADD auto_generated TINYINT NOT NULL, DROP idRappel, DROP heureRappel, DROP createdAt, DROP autoGenerated, CHANGE idHabitude idHabitude INT DEFAULT NULL, CHANGE jours jours VARCHAR(50) NOT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE message message VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_rappel)');
        $this->addSql('ALTER TABLE rappel_habitude ADD CONSTRAINT FK_C4294AE0437E1404 FOREIGN KEY (idHabitude) REFERENCES habitude (id_habitude) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rappel_habitude RENAME INDEX fk_rappel_habitude TO IDX_C4294AE0437E1404');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY `session_ibfk_1`');
        $this->addSql('ALTER TABLE session MODIFY idSession INT NOT NULL');
        $this->addSql('ALTER TABLE session ADD id_session INT NOT NULL, ADD date_debut DATETIME NOT NULL, ADD date_fin DATETIME NOT NULL, ADD duree_reelle INT NOT NULL, DROP idSession, DROP dateDebut, DROP dateFin, DROP dureeReelle, CHANGE Resultat resultat VARCHAR(255) NOT NULL, CHANGE commentaires commentaires LONGTEXT NOT NULL, CHANGE terminee terminee TINYINT NOT NULL, CHANGE dateSession date_session DATE NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_session)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4FB644627 FOREIGN KEY (idEx) REFERENCES exercice (id_ex) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session RENAME INDEX idex TO IDX_D044D5D4FB644627');
        $this->addSql('ALTER TABLE suivihabitude DROP FOREIGN KEY `fk_suivi_habitude`');
        $this->addSql('DROP INDEX uk_suivi_unique_day ON suivihabitude');
        $this->addSql('ALTER TABLE suivihabitude MODIFY idSuivi INT NOT NULL');
        $this->addSql('ALTER TABLE suivihabitude ADD id_suivi INT NOT NULL, DROP idSuivi, CHANGE idHabitude idHabitude INT DEFAULT NULL, CHANGE valeur valeur INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_suivi)');
        $this->addSql('ALTER TABLE suivihabitude ADD CONSTRAINT FK_E1522B87437E1404 FOREIGN KEY (idHabitude) REFERENCES habitude (id_habitude) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE suivihabitude RENAME INDEX idx_suivi_habitude TO IDX_E1522B87437E1404');
        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY `todo_ibfk_1`');
        $this->addSql('ALTER TABLE todo MODIFY idTodo INT NOT NULL');
        $this->addSql('ALTER TABLE todo ADD id_todo INT NOT NULL, ADD date_creation DATETIME NOT NULL, ADD date_echeance DATE NOT NULL, ADD date_completion DATETIME NOT NULL, ADD temps_estime INT NOT NULL, DROP idTodo, DROP dateCreation, DROP dateEcheance, DROP dateCompletion, DROP tempsEstime, CHANGE description description LONGTEXT NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE priorite priorite VARCHAR(50) NOT NULL, CHANGE progression progression INT NOT NULL, CHANGE notes notes LONGTEXT NOT NULL, CHANGE couleur couleur VARCHAR(7) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_todo)');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT FK_5A0EB6A0B7BBED16 FOREIGN KEY (idExercice) REFERENCES exercice (id_ex) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE todo RENAME INDEX idexercice TO IDX_5A0EB6A0B7BBED16');
        $this->addSql('DROP INDEX uk_utilisateur_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur MODIFY idU INT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD nom_u VARCHAR(255) NOT NULL, ADD prenom_u VARCHAR(255) NOT NULL, ADD email_u VARCHAR(255) NOT NULL, ADD mdps_u VARCHAR(255) NOT NULL, ADD age_u INT NOT NULL, ADD role_u VARCHAR(20) NOT NULL, DROP idU, DROP nomU, DROP prenomU, DROP emailU, DROP mdpsU, DROP roleU, CHANGE face_subject face_subject VARCHAR(255) NOT NULL, CHANGE face_image_id face_image_id VARCHAR(64) NOT NULL, CHANGE face_enabled face_enabled TINYINT NOT NULL, CHANGE profile_picture_path profile_picture_path VARCHAR(512) NOT NULL, CHANGE totp_secret totp_secret VARCHAR(128) NOT NULL, CHANGE totp_enabled totp_enabled TINYINT NOT NULL, CHANGE ageU id_u INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_u)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE badge_progression CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE exercices_completes exercices_completes INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE exercice ADD idEx INT AUTO_INCREMENT NOT NULL, DROP id_ex, CHANGE difficulte difficulte VARCHAR(50) DEFAULT \'\'\'Débutant\'\'\', CHANGE description description TEXT DEFAULT NULL, CHANGE demarche demarche TEXT DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT \'current_timestamp()\', CHANGE date_modification date_modification DATETIME DEFAULT \'NULL\', DROP PRIMARY KEY, ADD PRIMARY KEY (idEx)');
        $this->addSql('ALTER TABLE habitude ADD idHabitude INT AUTO_INCREMENT NOT NULL, ADD habitType VARCHAR(10) DEFAULT \'\'\'BOOLEAN\'\'\' NOT NULL, ADD targetValue INT DEFAULT 1 NOT NULL, DROP id_habitude, DROP habit_type, DROP target_value, CHANGE unit unit VARCHAR(20) DEFAULT \'\'\'\'\'\' NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idHabitude)');
        $this->addSql('ALTER TABLE humeur ADD idH INT AUTO_INCREMENT NOT NULL, DROP id_h, CHANGE type_humeur TypeHumeur VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idH)');
        $this->addSql('CREATE INDEX idx_humeur_date ON humeur (date)');
        $this->addSql('ALTER TABLE jalonprogression DROP FOREIGN KEY FK_2A71AD13672EC9EB');
        $this->addSql('ALTER TABLE jalonprogression ADD idJalon INT AUTO_INCREMENT NOT NULL, ADD dateCible DATE NOT NULL, ADD dateAtteinte DATE DEFAULT \'NULL\', ADD pourcentageProgression INT DEFAULT 0 NOT NULL, DROP id_jalon, DROP date_cible, DROP date_atteinte, DROP pourcentage_progression, CHANGE atteint atteint TINYINT DEFAULT 0 NOT NULL, CHANGE idObj idObj INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idJalon)');
        $this->addSql('ALTER TABLE jalonprogression ADD CONSTRAINT `fk_jalon_obj` FOREIGN KEY (idObj) REFERENCES objectif (idObj) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jalonprogression RENAME INDEX idx_2a71ad13672ec9eb TO idx_jalon_obj');
        $this->addSql('ALTER TABLE journalemotionnel ADD idJ INT AUTO_INCREMENT NOT NULL, ADD dateCreation DATETIME DEFAULT \'NULL\', DROP id_j, DROP date_creation, CHANGE note_personnelle NotePersonnelle VARCHAR(255) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idJ)');
        $this->addSql('CREATE INDEX idx_journal_date ON journalemotionnel (dateCreation)');
        $this->addSql('ALTER TABLE objectif ADD idObj INT AUTO_INCREMENT NOT NULL, ADD dateDebut DATE NOT NULL, ADD dateFin DATE NOT NULL, DROP id_obj, DROP date_debut, DROP date_fin, CHANGE descriprion descriprion VARCHAR(255) DEFAULT \'NULL\', DROP PRIMARY KEY, ADD PRIMARY KEY (idObj)');
        $this->addSql('CREATE INDEX idx_objectif_dates ON objectif (dateDebut, dateFin)');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('ALTER TABLE password_reset_tokens CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE expires_at expires_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE used used TINYINT DEFAULT 0 NOT NULL, CHANGE attempts attempts INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (user_id) REFERENCES utilisateur (idU) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_password_reset_user_created ON password_reset_tokens (user_id, created_at)');
        $this->addSql('ALTER TABLE planaction DROP FOREIGN KEY FK_9E5225F2672EC9EB');
        $this->addSql('ALTER TABLE planaction ADD idPlan INT AUTO_INCREMENT NOT NULL, DROP id_plan, CHANGE idObj idObj INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idPlan)');
        $this->addSql('ALTER TABLE planaction ADD CONSTRAINT `fk_plan_objectif` FOREIGN KEY (idObj) REFERENCES objectif (idObj) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planaction RENAME INDEX idx_9e5225f2672ec9eb TO idx_plan_objectif');
        $this->addSql('ALTER TABLE planificateurintelligent DROP INDEX IDX_B6FD5C8B672EC9EB, ADD UNIQUE INDEX uk_planif_obj (idObj)');
        $this->addSql('ALTER TABLE planificateurintelligent DROP FOREIGN KEY FK_B6FD5C8B672EC9EB');
        $this->addSql('ALTER TABLE planificateurintelligent ADD idPlanificateur INT AUTO_INCREMENT NOT NULL, ADD modeOrganisation VARCHAR(30) DEFAULT \'\'\'priorite\'\'\' NOT NULL, ADD capaciteQuotidienne INT DEFAULT 3 NOT NULL, ADD derniereGeneration DATETIME DEFAULT \'NULL\', DROP id_planificateur, DROP mode_organisation, DROP capacite_quotidienne, DROP derniere_generation, CHANGE idObj idObj INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idPlanificateur)');
        $this->addSql('ALTER TABLE planificateurintelligent ADD CONSTRAINT `fk_planif_obj` FOREIGN KEY (idObj) REFERENCES objectif (idObj) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profilpsychologique DROP INDEX IDX_796DA445A2D72265, ADD UNIQUE INDEX uk_profil_user (idU)');
        $this->addSql('ALTER TABLE profilpsychologique DROP FOREIGN KEY FK_796DA445A2D72265');
        $this->addSql('ALTER TABLE profilpsychologique ADD idP INT AUTO_INCREMENT NOT NULL, ADD NiveauStress INT NOT NULL, ADD NiveauMotivation INT NOT NULL, DROP id_p, DROP niveau_stress, DROP niveau_motivation, CHANGE description Description VARCHAR(255) DEFAULT \'NULL\', CHANGE idU idU INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idP)');
        $this->addSql('ALTER TABLE profilpsychologique ADD CONSTRAINT `fk_profil_user` FOREIGN KEY (idU) REFERENCES utilisateur (idU) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression ADD idProgression INT AUTO_INCREMENT NOT NULL, ADD idJalon INT DEFAULT NULL, ADD dateRealisation DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD scoreObtenu INT DEFAULT NULL, ADD ressentiUtilisateur INT DEFAULT NULL, ADD notesPersonnelles TEXT DEFAULT NULL, ADD tempsPasse INT DEFAULT 0 NOT NULL, ADD idEx INT DEFAULT NULL, ADD idSession INT DEFAULT NULL, ADD dateAtteinte DATETIME DEFAULT \'NULL\', ADD pourcentageProgression INT DEFAULT 0 NOT NULL, DROP id_progression, DROP id_jalon, DROP date_realisation, DROP score_obtenu, DROP ressenti_utilisateur, DROP notes_personnelles, DROP temps_passe, DROP id_ex, DROP id_session, DROP date_atteinte, DROP pourcentage_progression, CHANGE atteint atteint TINYINT DEFAULT 0 NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idProgression)');
        $this->addSql('ALTER TABLE rappel_habitude DROP FOREIGN KEY FK_C4294AE0437E1404');
        $this->addSql('ALTER TABLE rappel_habitude ADD idRappel INT AUTO_INCREMENT NOT NULL, ADD heureRappel TIME NOT NULL, ADD createdAt DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD autoGenerated TINYINT DEFAULT 0 NOT NULL, DROP id_rappel, DROP heure_rappel, DROP created_at, DROP auto_generated, CHANGE jours jours VARCHAR(50) DEFAULT \'\'\'Lun,Mar,Mer,Jeu,Ven,Sam,Dim\'\'\' NOT NULL, CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE message message VARCHAR(255) DEFAULT \'NULL\', CHANGE idHabitude idHabitude INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idRappel)');
        $this->addSql('ALTER TABLE rappel_habitude ADD CONSTRAINT `fk_rappel_habitude` FOREIGN KEY (idHabitude) REFERENCES habitude (idHabitude) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_rappel_actif_heure ON rappel_habitude (actif, heureRappel)');
        $this->addSql('ALTER TABLE rappel_habitude RENAME INDEX idx_c4294ae0437e1404 TO fk_rappel_habitude');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4FB644627');
        $this->addSql('ALTER TABLE session ADD idSession INT AUTO_INCREMENT NOT NULL, ADD dateDebut DATETIME DEFAULT \'NULL\', ADD dateFin DATETIME DEFAULT \'NULL\', ADD dureeReelle INT DEFAULT NULL, DROP id_session, DROP date_debut, DROP date_fin, DROP duree_reelle, CHANGE resultat Resultat VARCHAR(255) DEFAULT \'NULL\', CHANGE commentaires commentaires TEXT DEFAULT NULL, CHANGE terminee terminee TINYINT DEFAULT 0, CHANGE date_session dateSession DATE NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idSession)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT `session_ibfk_1` FOREIGN KEY (idEx) REFERENCES exercice (idEx)');
        $this->addSql('ALTER TABLE session RENAME INDEX idx_d044d5d4fb644627 TO idEx');
        $this->addSql('ALTER TABLE suivihabitude DROP FOREIGN KEY FK_E1522B87437E1404');
        $this->addSql('ALTER TABLE suivihabitude ADD idSuivi INT AUTO_INCREMENT NOT NULL, DROP id_suivi, CHANGE valeur valeur INT DEFAULT 0 NOT NULL, CHANGE idHabitude idHabitude INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idSuivi)');
        $this->addSql('ALTER TABLE suivihabitude ADD CONSTRAINT `fk_suivi_habitude` FOREIGN KEY (idHabitude) REFERENCES habitude (idHabitude) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uk_suivi_unique_day ON suivihabitude (idHabitude, date)');
        $this->addSql('ALTER TABLE suivihabitude RENAME INDEX idx_e1522b87437e1404 TO idx_suivi_habitude');
        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY FK_5A0EB6A0B7BBED16');
        $this->addSql('ALTER TABLE todo ADD idTodo INT AUTO_INCREMENT NOT NULL, ADD dateCreation DATETIME DEFAULT \'current_timestamp()\', ADD dateEcheance DATE DEFAULT \'NULL\', ADD dateCompletion DATETIME DEFAULT \'NULL\', ADD tempsEstime INT DEFAULT NULL, DROP id_todo, DROP date_creation, DROP date_echeance, DROP date_completion, DROP temps_estime, CHANGE description description TEXT DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'\'\'TODO\'\'\', CHANGE priorite priorite VARCHAR(50) DEFAULT \'\'\'MOYENNE\'\'\', CHANGE progression progression INT DEFAULT 0, CHANGE notes notes TEXT DEFAULT NULL, CHANGE couleur couleur VARCHAR(7) DEFAULT \'\'\'#3498db\'\'\', DROP PRIMARY KEY, ADD PRIMARY KEY (idTodo)');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT `todo_ibfk_1` FOREIGN KEY (idExercice) REFERENCES exercice (idEx)');
        $this->addSql('ALTER TABLE todo RENAME INDEX idx_5a0eb6a0b7bbed16 TO idExercice');
        $this->addSql('ALTER TABLE utilisateur ADD idU INT AUTO_INCREMENT NOT NULL, ADD nomU VARCHAR(255) NOT NULL, ADD prenomU VARCHAR(255) NOT NULL, ADD emailU VARCHAR(255) NOT NULL, ADD mdpsU VARCHAR(255) NOT NULL, ADD ageU INT NOT NULL, ADD roleU VARCHAR(20) DEFAULT \'\'\'USER\'\'\' NOT NULL, DROP id_u, DROP nom_u, DROP prenom_u, DROP email_u, DROP mdps_u, DROP age_u, DROP role_u, CHANGE face_subject face_subject VARCHAR(255) DEFAULT \'NULL\', CHANGE face_image_id face_image_id VARCHAR(64) DEFAULT \'NULL\', CHANGE face_enabled face_enabled TINYINT DEFAULT 0 NOT NULL, CHANGE profile_picture_path profile_picture_path VARCHAR(512) DEFAULT \'NULL\', CHANGE totp_secret totp_secret VARCHAR(128) DEFAULT \'NULL\', CHANGE totp_enabled totp_enabled TINYINT DEFAULT 0 NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idU)');
        $this->addSql('CREATE UNIQUE INDEX uk_utilisateur_email ON utilisateur (emailU)');
    }
}
