<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add client_suggestion_draft_tag join table for temporary tag drafts on Client';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        $isMysql = in_array($platform, ['mysql', 'mariadb'], true);

        if ($isMysql) {
            $this->addSql('CREATE TABLE client_suggestion_draft_tag (client_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_AD4FCD8E19EB6921 (client_id), INDEX IDX_AD4FCD8EBAD26311 (tag_id), PRIMARY KEY (client_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        } else {
            $this->addSql('CREATE TABLE client_suggestion_draft_tag (client_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_AD4FCD8E19EB6921 (client_id), INDEX IDX_AD4FCD8EBAD26311 (tag_id), PRIMARY KEY (client_id, tag_id))');
        }
        $this->addSql('ALTER TABLE client_suggestion_draft_tag ADD CONSTRAINT FK_AD4FCD8E19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client_suggestion_draft_tag ADD CONSTRAINT FK_AD4FCD8EBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client_suggestion_draft_tag DROP FOREIGN KEY FK_AD4FCD8E19EB6921');
        $this->addSql('ALTER TABLE client_suggestion_draft_tag DROP FOREIGN KEY FK_AD4FCD8EBAD26311');
        $this->addSql('DROP TABLE client_suggestion_draft_tag');
    }
}

