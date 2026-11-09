<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source dictionary table and client_source many-to-many relation, migrating existing client.source strings.';
    }

    public function up(Schema $schema): void
    {

        $this->addSql(
            'CREATE TABLE IF NOT EXISTS source (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_SOURCE_NAME (name),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE IF NOT EXISTS client_source (
                client_id INT NOT NULL,
                source_id INT NOT NULL,
                INDEX IDX_CLIENT_SOURCE_CLIENT (client_id),
                INDEX IDX_CLIENT_SOURCE_SOURCE (source_id),
                UNIQUE INDEX UNIQ_CLIENT_SOURCE (client_id, source_id),
                CONSTRAINT FK_CLIENT_SOURCE_CLIENT FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE,
                CONSTRAINT FK_CLIENT_SOURCE_SOURCE FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB'
        );

        $this->addSql(
            "INSERT INTO source (name)
             SELECT DISTINCT c.source
             FROM client c
             WHERE c.source IS NOT NULL
               AND c.source <> ''
               AND NOT EXISTS (
                   SELECT 1 FROM source s WHERE s.name = c.source
               )"
        );

        $this->addSql(
            'INSERT INTO client_source (client_id, source_id)
             SELECT DISTINCT c.id, s.id
             FROM client c
             INNER JOIN source s ON c.source = s.name
             WHERE c.source IS NOT NULL
               AND c.source <> \'\''
        );

        $this->addSql('ALTER TABLE client DROP COLUMN source');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE client ADD source VARCHAR(100) DEFAULT NULL');

        $this->addSql(
            'UPDATE client c
             LEFT JOIN client_source cs ON c.id = cs.client_id
             LEFT JOIN source s ON cs.source_id = s.id
             SET c.source = s.name
             WHERE c.source IS NULL'
        );

        $this->addSql('DROP TABLE client_source');
        $this->addSql('DROP TABLE source');
    }
}

