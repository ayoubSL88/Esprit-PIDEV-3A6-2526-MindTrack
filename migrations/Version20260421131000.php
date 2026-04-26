<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align habitude owner index name with Doctrine expected naming.';
    }

    public function up(Schema $schema): void
    {
        $oldIndexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_HABITUDE_USER_ID_U'") > 0;
        $newIndexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_10DD3E5F35F8C041'") > 0;
        $fkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'FK_HABITUDE_USER_ID_U'") > 0;

        if ($oldIndexExists && !$newIndexExists) {
            if ($fkExists) {
                $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_HABITUDE_USER_ID_U');
            }

            $this->addSql('DROP INDEX IDX_HABITUDE_USER_ID_U ON habitude');
            $this->addSql('CREATE INDEX IDX_10DD3E5F35F8C041 ON habitude (id_u)');

            if ($fkExists) {
                $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_HABITUDE_USER_ID_U FOREIGN KEY (id_u) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        $oldIndexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_HABITUDE_USER_ID_U'") > 0;
        $newIndexExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND INDEX_NAME = 'IDX_10DD3E5F35F8C041'") > 0;
        $fkExists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitude' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'FK_HABITUDE_USER_ID_U'") > 0;

        if ($newIndexExists && !$oldIndexExists) {
            if ($fkExists) {
                $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_HABITUDE_USER_ID_U');
            }

            $this->addSql('DROP INDEX IDX_10DD3E5F35F8C041 ON habitude');
            $this->addSql('CREATE INDEX IDX_HABITUDE_USER_ID_U ON habitude (id_u)');

            if ($fkExists) {
                $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_HABITUDE_USER_ID_U FOREIGN KEY (id_u) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
            }
        }
    }
}