<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make client.nip nullable (NULL = działalność nierejestrowana)';
    }

    public function up(Schema $schema): void
    {

        $this->addSql("ALTER TABLE client MODIFY nip VARCHAR(20) DEFAULT NULL");

        $this->addSql("UPDATE client SET nip = NULL WHERE nip = ''");
    }

    public function down(Schema $schema): void
    {

        $this->addSql("UPDATE client SET nip = CONCAT('BRAK-', id) WHERE nip IS NULL");
        $this->addSql("ALTER TABLE client MODIFY nip VARCHAR(20) NOT NULL");
    }
}

