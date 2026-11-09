<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_by to client';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_CLIENT_CREATED_BY ON client (created_by_id)');
        $this->addSql("UPDATE client c SET created_by_id = (SELECT al.user_id FROM activity_log al WHERE al.entity_type = 'Client' AND al.action = 'create' AND al.entity_id = c.id AND al.user_id IS NOT NULL ORDER BY al.created_at ASC, al.id ASC LIMIT 1) WHERE c.created_by_id IS NULL");
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_CLIENT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_CLIENT_CREATED_BY');
        $this->addSql('DROP INDEX IDX_CLIENT_CREATED_BY ON client');
        $this->addSql('ALTER TABLE client DROP created_by_id');
    }
}

