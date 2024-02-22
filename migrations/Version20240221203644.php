<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240221203644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE downloaded_files');
        $this->addSql('ALTER TABLE bean ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME NOT NULL, ADD status VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE status status VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE coffee CHANGE status status VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE loaded_file CHANGE status status VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE taste ADD status VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE downloaded_files (id INT AUTO_INCREMENT NOT NULL, real_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, real_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, public_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mime_type VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, create_at DATETIME NOT NULL, status VARCHAR(24) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE coffee CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE taste DROP status');
        $this->addSql('ALTER TABLE category CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE loaded_file CHANGE status status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE bean DROP created_at, DROP updated_at, DROP status');
    }
}
