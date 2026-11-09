<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contact_method table and client_contact_method pivot; migrate data from JSON preferred_contact_method; drop old column';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('CREATE TABLE contact_method (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(50) DEFAULT NULL,
            UNIQUE INDEX UNIQ_CM_NAME (name),
            UNIQUE INDEX UNIQ_CM_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE client_contact_method (
            client_id INT NOT NULL,
            contact_method_id INT NOT NULL,
            INDEX IDX_CCM_CLIENT (client_id),
            INDEX IDX_CCM_METHOD (contact_method_id),
            PRIMARY KEY(client_id, contact_method_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE client_contact_method
            ADD CONSTRAINT FK_CCM_CLIENT FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_CCM_METHOD FOREIGN KEY (contact_method_id) REFERENCES contact_method (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO contact_method (name, slug) VALUES
            ('SMS', 'sms'),
            ('E-mail', 'mail'),
            ('Telefon', 'telefon'),
            ('Nie kontaktować się', 'nie_kontaktowac')");

        $this->addSql("INSERT INTO client_contact_method (client_id, contact_method_id)
            SELECT c.id, cm.id
            FROM client c
            JOIN contact_method cm ON JSON_CONTAINS(c.preferred_contact_method, JSON_QUOTE(cm.slug))
            WHERE c.preferred_contact_method IS NOT NULL AND c.preferred_contact_method != 'null'");

        $this->addSql('ALTER TABLE client DROP COLUMN preferred_contact_method');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD preferred_contact_method JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE client_contact_method DROP FOREIGN KEY FK_CCM_CLIENT');
        $this->addSql('ALTER TABLE client_contact_method DROP FOREIGN KEY FK_CCM_METHOD');
        $this->addSql('DROP TABLE client_contact_method');
        $this->addSql('DROP TABLE contact_method');
    }
}

