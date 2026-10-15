<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260914195631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega comprobante y numero a cas.orden';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cas.orden ADD comprobante VARCHAR(255) DEFAULT 'HESR' NOT NULL");
        $this->addSql('ALTER TABLE cas.orden ADD numero INTEGER NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cas.orden DROP COLUMN comprobante');
        $this->addSql('ALTER TABLE cas.orden DROP COLUMN numero');
    }
}
