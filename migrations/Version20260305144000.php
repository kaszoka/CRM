<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305144000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update task.assigned_to_id foreign key to ON DELETE SET NULL so users can be deleted without removing tasks.';
    }

    public function up(Schema $schema): void
    {

        try {
            $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F4BD7827');
        } catch (\Throwable $e) {

        }

        $this->addSql(
            'ALTER TABLE task
             ADD CONSTRAINT FK_527EDB25F4BD7827
             FOREIGN KEY (assigned_to_id) REFERENCES `user` (id) ON DELETE SET NULL'
        );
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F4BD7827');
        $this->addSql(
            'ALTER TABLE task
             ADD CONSTRAINT FK_527EDB25F4BD7827
             FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)'
        );
    }
}

