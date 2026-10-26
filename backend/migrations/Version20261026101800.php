<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261026101800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Таблицы заказов, позиций и тарифов доставки';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE delivery_tariff (
            code VARCHAR(32) NOT NULL,
            label VARCHAR(64) NOT NULL,
            base_kop INT NOT NULL,
            free_from_kop INT DEFAULT NULL,
            PRIMARY KEY(code)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE customer_order (
            id INT AUTO_INCREMENT NOT NULL,
            shop_id VARCHAR(64) NOT NULL,
            external_id VARCHAR(64) NOT NULL,
            status VARCHAR(16) NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(12) NOT NULL,
            delivery_type VARCHAR(16) NOT NULL,
            district VARCHAR(32) DEFAULT NULL,
            address VARCHAR(512) DEFAULT NULL,
            delivery_date DATE DEFAULT NULL,
            time_from TIME DEFAULT NULL,
            time_to TIME DEFAULT NULL,
            marketplace_created_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            items_total_kop INT NOT NULL,
            declared_total_kop INT DEFAULT NULL,
            needs_review TINYINT(1) NOT NULL,
            delivery_base_kop INT NOT NULL,
            delivery_free_from_kop INT DEFAULT NULL,
            delivery_free_applied TINYINT(1) NOT NULL,
            delivery_urgent TINYINT(1) NOT NULL,
            delivery_urgency_kop INT NOT NULL,
            delivery_total_kop INT NOT NULL,
            grand_total_kop INT NOT NULL,
            imported_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            status_updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX uniq_shop_external (shop_id, external_id),
            INDEX idx_shop_status (shop_id, status),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE order_item (
            id INT AUTO_INCREMENT NOT NULL,
            order_id INT NOT NULL,
            position INT NOT NULL,
            sku VARCHAR(64) DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            qty INT NOT NULL,
            price_kop INT NOT NULL,
            INDEX IDX_52EA1F098D9F6D38 (order_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO delivery_tariff (code, label, base_kop, free_from_kop) VALUES
            ('centre', 'Центр', 30000, 500000),
            ('north', 'Север', 40000, 700000),
            ('south', 'Юг', 40000, 700000),
            ('suburb', 'Пригород', 70000, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE delivery_tariff');
    }
}
