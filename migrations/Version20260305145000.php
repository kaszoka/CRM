<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305145000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add must_change_password flag to user for first-login password reset flow.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` ADD must_change_password TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` DROP must_change_password");
    }
}

