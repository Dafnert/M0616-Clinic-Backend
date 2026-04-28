<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the odontogram table linked to patient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE odontogram (
                id          INT AUTO_INCREMENT NOT NULL,
                patient_id  INT NOT NULL,
                tooth_number SMALLINT NOT NULL,
                status      VARCHAR(50) NOT NULL DEFAULT \'healthy\',
                notes       VARCHAR(500) DEFAULT NULL,
                created_at  DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at  DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UQ_patient_tooth (patient_id, tooth_number),
                INDEX IDX_patient (patient_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE odontogram
                ADD CONSTRAINT FK_odontogram_patient
                FOREIGN KEY (patient_id) REFERENCES patient (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odontogram DROP FOREIGN KEY FK_odontogram_patient');
        $this->addSql('DROP TABLE odontogram');
    }
}