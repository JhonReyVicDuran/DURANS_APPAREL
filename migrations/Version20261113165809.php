<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113165809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account creation timestamps to existing users.';
    }

    public function up(Schema $schema): void
    {
        // First, add the column as nullable.
        $this->addSql(
            'ALTER TABLE user ADD created_at DATETIME DEFAULT NULL'
        );

        // Give existing accounts a timestamp.
        $this->addSql(
            'UPDATE user SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL'
        );

        // Require a timestamp for all accounts.
        $this->addSql(
            'ALTER TABLE user MODIFY created_at DATETIME NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP created_at');
    }
}