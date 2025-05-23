<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523143245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category_archives (category_id INT NOT NULL, archives_id INT NOT NULL, INDEX IDX_5518684212469DE2 (category_id), INDEX IDX_551868421C8CA759 (archives_id), PRIMARY KEY(category_id, archives_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE citation (id INT AUTO_INCREMENT NOT NULL, description LONGTEXT NOT NULL, auteur VARCHAR(45) NOT NULL, date DATE NOT NULL, image VARCHAR(500) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE citation_archives (citation_id INT NOT NULL, archives_id INT NOT NULL, INDEX IDX_794704BF500A8AB7 (citation_id), INDEX IDX_794704BF1C8CA759 (archives_id), PRIMARY KEY(citation_id, archives_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_archives ADD CONSTRAINT FK_5518684212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_archives ADD CONSTRAINT FK_551868421C8CA759 FOREIGN KEY (archives_id) REFERENCES archives (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE citation_archives ADD CONSTRAINT FK_794704BF500A8AB7 FOREIGN KEY (citation_id) REFERENCES citation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE citation_archives ADD CONSTRAINT FK_794704BF1C8CA759 FOREIGN KEY (archives_id) REFERENCES archives (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE category_archives DROP FOREIGN KEY FK_5518684212469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category_archives DROP FOREIGN KEY FK_551868421C8CA759
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE citation_archives DROP FOREIGN KEY FK_794704BF500A8AB7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE citation_archives DROP FOREIGN KEY FK_794704BF1C8CA759
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category_archives
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE citation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE citation_archives
        SQL);
    }
}
