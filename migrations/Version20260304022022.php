<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304022022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alumni_profile (id INT AUTO_INCREMENT NOT NULL, graduation_year INT DEFAULT NULL, program VARCHAR(100) DEFAULT NULL, company VARCHAR(150) DEFAULT NULL, job_title VARCHAR(150) DEFAULT NULL, location VARCHAR(150) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, linkedin_url VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_DC5ACEC1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE alumni_profile ADD CONSTRAINT FK_DC5ACEC1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alumni_profile DROP FOREIGN KEY FK_DC5ACEC1A76ED395');
        $this->addSql('DROP TABLE alumni_profile');
        $this->addSql('DROP TABLE users');
    }
}
