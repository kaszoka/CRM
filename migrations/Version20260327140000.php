<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change preferred_contact_method from VARCHAR to JSON (multi-select)';
    }

    public function up(Schema $schema): void
    {

        $this->addSql("UPDATE client SET preferred_contact_method = CASE
            WHEN preferred_contact_method = 'sms'  THEN '[\"sms\"]'
            WHEN preferred_contact_method = 'mail' THEN '[\"mail\"]'
            WHEN preferred_contact_method = 'oba'  THEN '[\"sms\",\"mail\"]'
            ELSE NULL
        END");
        $this->addSql('ALTER TABLE client CHANGE preferred_contact_method preferred_contact_method JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client CHANGE preferred_contact_method preferred_contact_method VARCHAR(10) DEFAULT NULL');
    }
}

