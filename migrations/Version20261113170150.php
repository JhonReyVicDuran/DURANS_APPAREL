<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261113170150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, actor_name VARCHAR(255) NOT NULL, actor_email VARCHAR(180) NOT NULL, action VARCHAR(30) NOT NULL, module VARCHAR(50) NOT NULL, record_id INT DEFAULT NULL, description LONGTEXT NOT NULL, old_values JSON DEFAULT NULL, new_values JSON DEFAULT NULL, created_at DATETIME NOT NULL, performed_by_id INT DEFAULT NULL, INDEX IDX_FD06F6472E65C292 (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F6472E65C292 FOREIGN KEY (performed_by_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F6472E65C292');
        $this->addSql('DROP TABLE activity_log');
    }
}
