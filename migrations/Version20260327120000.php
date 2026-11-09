<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to client table for soft delete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP deleted_at');
    }
}

