<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit;

use Horat1us\Yii\Configurator\Bootstrap;
use Horat1us\Yii\Configurator\Web\Bootstrap as WebBootstrap;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    private \yii\console\Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new \yii\console\Application([
            'id' => 'test',
            'basePath' => dirname(__DIR__, 2),
        ]);
        // Unregister Yii2 error/exception handlers so PHPUnit stays in control.
        $this->app->errorHandler->unregister();
    }

    protected function tearDown(): void
    {
        \Yii::$app = null;
        \Yii::$container = new \yii\di\Container();
        parent::tearDown();
    }

    public function testManagePermissionConfiguresSingleWebBootstrap(): void
    {
        $bootstrap = new Bootstrap(['managePermission' => 'configurator.manage']);
        $bootstrap->bootstrap($this->app);

        $this->assertTrue(array_is_list($bootstrap->chain));
        $web = array_values(array_filter(
            $bootstrap->chain,
            fn($element): bool => $element instanceof WebBootstrap
        ));
        $this->assertCount(1, $web);
        $this->assertSame('configurator.manage', $web[0]->managePermission);
    }

    public function testWithoutManagePermissionKeepsDefaultChain(): void
    {
        $bootstrap = new Bootstrap();
        $bootstrap->bootstrap($this->app);

        $this->assertCount(3, $bootstrap->chain);
        $web = array_values(array_filter(
            $bootstrap->chain,
            fn($element): bool => $element instanceof WebBootstrap
        ));
        $this->assertCount(1, $web);
        $this->assertNull($web[0]->managePermission);
    }
}
