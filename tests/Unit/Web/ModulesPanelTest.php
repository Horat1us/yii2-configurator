<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Registry;
use Horat1us\Yii\Configurator\Web\ModulesPanel;
use PHPUnit\Framework\TestCase;
use Wearesho\Yii\Http;
use yii\base\Component;

class ModulesPanelTest extends TestCase
{
    private Registry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $app = new \yii\console\Application([
            'id' => 'test',
            'basePath' => dirname(__DIR__, 3),
            'components' => [
                'user' => new class extends Component {
                    public function can(string $permission): bool
                    {
                        return $permission === 'allowed';
                    }
                },
            ],
        ]);
        // Unregister Yii2 error/exception handlers so PHPUnit stays in control.
        $app->errorHandler->unregister();
        $this->registry = new Registry();
        \Yii::$container->setSingleton(Registry::class, fn() => $this->registry);
    }

    protected function tearDown(): void
    {
        \Yii::$app = null;
        \Yii::$container = new \yii\di\Container();
        parent::tearDown();
    }

    public function testModulesIsListWhenFirstModuleIsNotPermitted(): void
    {
        $this->registry->register($this->makeModule('denied', 'denied'));
        $this->registry->register($this->makeModule('public', null));
        $this->registry->register($this->makeModule('allowed', 'allowed'));

        $modules = $this->getModules();

        $this->assertTrue(array_is_list($modules), 'modules must be serialized as JSON array, not object');
        $this->assertSame(['public', 'allowed'], array_column($modules, 'key'));
        $this->assertStringStartsWith('[', json_encode($modules));
    }

    public function testModulesContainsAllPermittedModules(): void
    {
        $this->registry->register($this->makeModule('public', null));
        $this->registry->register($this->makeModule('allowed', 'allowed'));

        $this->assertSame(['public', 'allowed'], array_column($this->getModules(), 'key'));
    }

    private function getModules(): array
    {
        $panel = new ModulesPanel(new Http\Request(), new Http\Response());
        return $panel->getResponse()->data['modules'];
    }

    private function makeModule(string $key, ?string $permission): Module
    {
        return new class ($key, $permission) extends Module {
            public function __construct(private string $k, private ?string $permission)
            {
                parent::__construct();
            }
            public function getKey(): string
            {
                return $this->k;
            }
            public function getModuleLabel(): string
            {
                return ucfirst($this->k);
            }
            public function defaults(): array
            {
                return [];
            }
            public function rules(): array
            {
                return [];
            }
            public function getRequiredPermission(): ?string
            {
                return $this->permission;
            }
        };
    }
}
