<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129215956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sheet_tag (sheet_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(sheet_id, tag_id), CONSTRAINT FK_CE6A9A718B1206A5 FOREIGN KEY (sheet_id) REFERENCES sheet (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE6A9A71BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE6A9A718B1206A5 ON sheet_tag (sheet_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE6A9A71BAD26311 ON sheet_tag (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sheets_tags
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE sheets_tags (sheet_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(sheet_id, tag_id), CONSTRAINT FK_21A233D98B1206A5 FOREIGN KEY (sheet_id) REFERENCES sheet (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_21A233D9BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_21A233D9BAD26311 ON sheets_tags (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_21A233D98B1206A5 ON sheets_tags (sheet_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sheet_tag
        SQL);
    }
}
