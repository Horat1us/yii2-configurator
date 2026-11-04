<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Integration;

use Horat1us\Yii\Configurator\Console\Controller;
use Horat1us\Yii\Configurator\Registry;
use Horat1us\Yii\Configurator\Repository;
use yii\console\ExitCode;

class ConsoleControllerTest extends TestCase
{
    private Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();

        \Yii::$container->setSingleton(Registry::class);
        \Yii::$container->get(Registry::class)->register(new TestModule());
        $this->controller = new class ('configurator', \Yii::$app) extends Controller {
            public string $out = '';
            public string $err = '';

            public function stdout($string): int
            {
                $this->out .= $string;
                return strlen($string);
            }

            public function stderr($string): int
            {
                $this->err .= $string;
                return strlen($string);
            }
        };
    }

    public function testSetStoresArrayAsNormalizedJson(): void
    {
        $exitCode = $this->controller->actionSet('test', 'tags', '[ "cpa",  "ґанок/сад" ]');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertSame('["cpa","ґанок/сад"]', (new Repository())->get('test', 'tags'));
        $this->assertSame(['cpa', 'ґанок/сад'], (new Repository())->getModule(TestModule::class)->tags);
    }

    public function testSetRejectsArrayValueThatIsNotJsonArray(): void
    {
        $exitCode = $this->controller->actionSet('test', 'tags', 'cpa,cpa2');

        $this->assertSame(ExitCode::DATAERR, $exitCode);
        $this->assertStringContainsString('pass it as JSON', $this->controller->err);
        $this->assertSame('', (new Repository())->get('test', 'tags'), 'nothing is stored');
    }

    public function testSetStoresScalarAsGiven(): void
    {
        $exitCode = $this->controller->actionSet('test', 'limit', '42');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertSame('42', (new Repository())->get('test', 'limit'));
    }

    public function testGetPrintsArrayAsJson(): void
    {
        (new Repository())->set('test', 'tags', '["beta"]');

        $exitCode = $this->controller->actionGet('test');

        $this->assertSame(ExitCode::OK, $exitCode);
        $this->assertStringContainsString('tags: ["beta"] (default: ["alpha"])', $this->controller->out);
        $this->assertStringContainsString("limit: 10 (default: 10)", $this->controller->out);
    }
}
