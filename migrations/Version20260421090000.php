<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore habitude owner foreign key dropped by mistake during merge.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habitude ADD idU INT DEFAULT NULL');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_AA5D0867A2D72265 FOREIGN KEY (idU) REFERENCES utilisateur (id_u) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_AA5D0867A2D72265 ON habitude (idU)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_AA5D0867A2D72265');
        $this->addSql('DROP INDEX IDX_AA5D0867A2D72265 ON habitude');
        $this->addSql('ALTER TABLE habitude DROP idU');
    }
}
