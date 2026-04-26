<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411092000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align Doctrine index name for the habitude owner relation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_AA5D0867A2D72265');
        $this->addSql('DROP INDEX IDX_AA5D0867A2D72265 ON habitude');
        $this->addSql('CREATE INDEX IDX_10DD3E5FA2D72265 ON habitude (idU)');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_AA5D0867A2D72265 FOREIGN KEY (idU) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_AA5D0867A2D72265');
        $this->addSql('DROP INDEX IDX_10DD3E5FA2D72265 ON habitude');
        $this->addSql('CREATE INDEX IDX_AA5D0867A2D72265 ON habitude (idU)');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_AA5D0867A2D72265 FOREIGN KEY (idU) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
    }
}
