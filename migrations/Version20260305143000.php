<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create client_source join table and align schema with Source many-to-many relation';
    }

    public function up(Schema $schema): void
    {

        $this->addSql(
            'CREATE TABLE client_source (
                client_id INT NOT NULL,
                source_id INT NOT NULL,
                INDEX IDX_A2C34C019EB6921 (client_id),
                INDEX IDX_A2C34C0953C1C61 (source_id),
                PRIMARY KEY (client_id, source_id)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE client_source
             ADD CONSTRAINT FK_A2C34C019EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE client_source
             ADD CONSTRAINT FK_A2C34C0953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE'
        );

        try {
            $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_CLIENT_SOURCE');
        } catch (\Throwable $e) {

        }

        try {
            $this->addSql('DROP INDEX FK_CLIENT_SOURCE ON client');
        } catch (\Throwable $e) {

        }

        try {
            $this->addSql('ALTER TABLE client DROP source_id');
        } catch (\Throwable $e) {

        }
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE client ADD source_id INT DEFAULT NULL');
        $this->addSql(
            'ALTER TABLE client
             ADD CONSTRAINT FK_CLIENT_SOURCE FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE SET NULL'
        );
        $this->addSql('CREATE INDEX FK_CLIENT_SOURCE ON client (source_id)');

        $this->addSql('DROP TABLE client_source');
    }
}

