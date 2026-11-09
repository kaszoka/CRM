<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326235500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by to task and suggestion';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE suggestion ADD created_by_id INT DEFAULT NULL');

        $this->addSql('CREATE INDEX IDX_527EDB2516CDBFB ON task (created_by_id)');
        $this->addSql('CREATE INDEX IDX_7C0D0D5F16CDBFB ON suggestion (created_by_id)');

        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2516CDBFB FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE suggestion ADD CONSTRAINT FK_7C0D0D5F16CDBFB FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2516CDBFB');
        $this->addSql('ALTER TABLE suggestion DROP FOREIGN KEY FK_7C0D0D5F16CDBFB');

        $this->addSql('DROP INDEX IDX_527EDB2516CDBFB ON task');
        $this->addSql('DROP INDEX IDX_7C0D0D5F16CDBFB ON suggestion');

        $this->addSql('ALTER TABLE task DROP created_by_id');
        $this->addSql('ALTER TABLE suggestion DROP created_by_id');
    }
}

