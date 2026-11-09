<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add suggestion title, tag table and suggestion_tag pivot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE suggestion ADD title VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_389B7835E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE suggestion_tag (suggestion_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_BD99854CA41BB822 (suggestion_id), INDEX IDX_BD99854CBAD26311 (tag_id), PRIMARY KEY(suggestion_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE suggestion_tag ADD CONSTRAINT FK_BD99854CA41BB822 FOREIGN KEY (suggestion_id) REFERENCES suggestion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE suggestion_tag ADD CONSTRAINT FK_BD99854CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE suggestion_tag DROP FOREIGN KEY FK_BD99854CA41BB822');
        $this->addSql('ALTER TABLE suggestion_tag DROP FOREIGN KEY FK_BD99854CBAD26311');
        $this->addSql('DROP TABLE suggestion_tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('ALTER TABLE suggestion DROP title');
    }
}

