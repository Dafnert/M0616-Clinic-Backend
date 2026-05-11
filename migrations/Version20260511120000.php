<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add file_data blob column to document table';
    }

    public function up(Schema $schema): void
    {
        // file_data column already exists from Version20260503140405
        // $this->addSql('ALTER TABLE document ADD file_data LONGBLOB DEFAULT NULL COMMENT \'(DC2Type:blob)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP file_data');
    }
}
