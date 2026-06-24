<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Registry;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidArgumentException;

class RegistryTest extends TestCase
{
    private Registry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new Registry();
    }

    public function testRegisterAndGet(): void
    {
        $module = $this->makeModule('test');
        $this->registry->register($module);

        $this->assertSame($module, $this->registry->get('test'));
    }

    public function testHasReturnsFalseForUnknownKey(): void
    {
        $this->assertFalse($this->registry->has('unknown'));
    }

    public function testHasReturnsTrueAfterRegister(): void
    {
        $this->registry->register($this->makeModule('foo'));
        $this->assertTrue($this->registry->has('foo'));
    }

    public function testGetThrowsForUnknownKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->registry->get('missing');
    }

    public function testAllReturnsAllModules(): void
    {
        $a = $this->makeModule('a');
        $b = $this->makeModule('b');
        $this->registry->register($a);
        $this->registry->register($b);

        $this->assertSame([$a, $b], $this->registry->all());
    }

    public function testRegisterOverwritesExistingKey(): void
    {
        $first  = $this->makeModule('x');
        $second = $this->makeModule('x');
        $this->registry->register($first);
        $this->registry->register($second);

        $this->assertSame($second, $this->registry->get('x'));
    }

    private function makeModule(string $key): Module
    {
        return new class ($key) extends Module {
            public function __construct(private string $k)
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
        };
    }
}
