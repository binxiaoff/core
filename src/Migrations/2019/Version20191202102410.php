<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191202102410 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '(FRONT-102) Remove call to the gulp assets package';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $searchString = $this->connection->quote(", 'gulp')", ParameterType::STRING);

        $this->addSql("UPDATE mail_template SET content=REPLACE(content, {$searchString}, ')')");
        $this->addSql("UPDATE mail_footer SET content=REPLACE(content, {$searchString}, ')')");
        $this->addSql("UPDATE mail_header SET content=REPLACE(content, {$searchString}, ')')");
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(true);
    }
}
