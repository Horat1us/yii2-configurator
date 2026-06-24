<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Migrations;

use yii\db\Migration;

/**
 * Creates `configurator_entry` and `configurator_history` tables.
 *
 * The `user_id` column stores a plain integer with no FK constraint. If your project
 * needs a FK to its user table, add it in a separate consuming-project migration:
 *
 *   $this->addForeignKey(
 *       'fk_configurator_entry_user',
 *       'configurator_entry', 'user_id',
 *       'users', 'id',
 *       'SET NULL', 'CASCADE',
 *   );
 */
class M000000000001CreateConfiguratorTables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('configurator_entry', [
            'id'         => $this->primaryKey(),
            'key'        => $this->string(255)->notNull()->unique(),
            'value'      => $this->text()->notNull(),
            'user_id'    => $this->integer()->null()
                ->comment('Set by consuming project; add FK in your own migration.'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('NOW()'),
        ]);

        $this->createTable('configurator_history', [
            'id'         => $this->primaryKey(),
            'key'        => $this->string(255)->notNull(),
            'value'      => $this->text()->notNull(),
            'user_id'    => $this->integer()->null()
                ->comment('Set by consuming project; add FK in your own migration.'),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('NOW()'),
        ]);

        $this->createIndex('idx_configurator_history_key', 'configurator_history', 'key');
        $this->createIndex('idx_configurator_history_created_at', 'configurator_history', 'created_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('configurator_history');
        $this->dropTable('configurator_entry');
    }
}
