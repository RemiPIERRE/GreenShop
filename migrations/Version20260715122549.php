<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715122549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. On remplit les slugs existants avec une valeur unique
        $this->addSql("UPDATE product SET slug = CONCAT('produit-', id) WHERE slug = '' OR slug IS NULL");

        // 2. Maintenant que chaque ligne a un slug unique, on peut ajouter la contrainte
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D34A04AD989D9B62 ON product');
    }
}
