<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename utilisateur.id_u to user_id and update related FKs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_HABITUDE_USER_ID_U');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_3967A216A76ED395');
        $this->addSql('ALTER TABLE profilpsychologique DROP FOREIGN KEY FK_796DA445A76ED395');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073A76ED395');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4A76ED395');

        $this->addSql('ALTER TABLE utilisateur CHANGE id_u user_id INT(11) NOT NULL');
        $this->addSql('ALTER TABLE habitude CHANGE id_u user_id INT(11) DEFAULT NULL');

        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_HABITUDE_USER_ID FOREIGN KEY (user_id) REFERENCES utilisateur (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_PASSWORD_RESET_TOKENS_USER_ID FOREIGN KEY (user_id) REFERENCES utilisateur (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profilpsychologique ADD CONSTRAINT FK_PROFILPSYCHOLOGIQUE_USER_ID FOREIGN KEY (user_id) REFERENCES utilisateur (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_PROGRESSION_USER_ID FOREIGN KEY (user_id) REFERENCES utilisateur (user_id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_SESSION_USER_ID FOREIGN KEY (user_id) REFERENCES utilisateur (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_SESSION_USER_ID');
        $this->addSql('ALTER TABLE progression DROP FOREIGN KEY FK_PROGRESSION_USER_ID');
        $this->addSql('ALTER TABLE profilpsychologique DROP FOREIGN KEY FK_PROFILPSYCHOLOGIQUE_USER_ID');
        $this->addSql('ALTER TABLE password_reset_tokens DROP FOREIGN KEY FK_PASSWORD_RESET_TOKENS_USER_ID');
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_HABITUDE_USER_ID');

        $this->addSql('ALTER TABLE habitude CHANGE user_id id_u INT(11) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE user_id id_u INT(11) NOT NULL');

        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u)');
        $this->addSql('ALTER TABLE progression ADD CONSTRAINT FK_D5B25073A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u)');
        $this->addSql('ALTER TABLE profilpsychologique ADD CONSTRAINT FK_796DA445A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_tokens ADD CONSTRAINT FK_3967A216A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id_u) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_HABITUDE_USER_ID_U FOREIGN KEY (id_u) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
    }
}
