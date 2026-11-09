<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google Authenticator 2FA fields to user entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` ADD google_authenticator_secret VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE `user` ADD google_authenticator_enabled TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` DROP google_authenticator_secret");
        $this->addSql("ALTER TABLE `user` DROP google_authenticator_enabled");
    }
}

