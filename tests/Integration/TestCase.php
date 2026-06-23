<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Integration;

use Horat1us\Yii\Configurator\Migrations\M000000000001CreateConfiguratorTables;
use PHPUnit\Framework\TestCase as BaseTestCase;
use yii\db\Connection;

abstract class TestCase extends BaseTestCase
{
    protected Connection $db;

    protected function setUp(): void
    {
        parent::setUp();

        $dsn      = getenv('DB_DSN')      ?: 'pgsql:host=127.0.0.1;port=5434;dbname=yii2_configurator_test';
        $user     = getenv('DB_USER')     ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: 'test';

        $this->db = new Connection([
            'dsn'      => $dsn,
            'username' => $user,
            'password' => $password,
        ]);
        $this->db->open();

        $app = new \yii\console\Application([
            'id'         => 'test',
            'basePath'   => dirname(__DIR__),
            'components' => [
                'db' => $this->db,
            ],
        ]);
        // Unregister Yii2 error/exception handlers so PHPUnit stays in control.
        $app->errorHandler->unregister();

        ob_start();
        $migration = new M000000000001CreateConfiguratorTables(['db' => $this->db]);
        $migration->up();
        ob_end_clean();

        \Yii::$app->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        $transaction = \Yii::$app->db->transaction;
        if ($transaction !== null && $transaction->isActive) {
            $transaction->rollBack();
        }

        ob_start();
        $migration = new M000000000001CreateConfiguratorTables(['db' => $this->db]);
        $migration->down();
        ob_end_clean();

        \Yii::$app->db->close();

        \Yii::$app = null;
        \Yii::$container = new \yii\di\Container();

        parent::tearDown();
    }
}
