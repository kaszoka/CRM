<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suggestion table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE suggestion (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_DD80F31B19EB6921 (client_id), INDEX idx_suggestion_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE suggestion ADD CONSTRAINT FK_DD80F31B19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suggestion DROP FOREIGN KEY FK_DD80F31B19EB6921');
        $this->addSql('DROP TABLE suggestion');
    }
}

