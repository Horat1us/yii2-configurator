<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Web\ModuleSerializerTrait;
use PHPUnit\Framework\TestCase;

/**
 * Pins the module response of the array settings documented in the README.
 */
class ModuleSerializerTest extends TestCase
{
    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new class extends Module {
            /** @var string[] */
            public array $methods = ['card'];
            /** @var string[] */
            public array $blockedReferrers = [];

            public function getKey(): string
            {
                return 'payment';
            }

            public function getModuleLabel(): string
            {
                return 'Payment Settings';
            }

            public function defaults(): array
            {
                return ['methods' => ['card'], 'blockedReferrers' => []];
            }

            public function rules(): array
            {
                return [
                    [['methods'], 'in', 'range' => ['card', 'bank', 'wallet'], 'allowArray' => true],
                    [['methods'], 'filter', 'filter' => fn(array $v) => array_values(array_unique($v))],
                    [['blockedReferrers'], 'each', 'rule' => ['string', 'max' => 255]],
                    [['blockedReferrers'], 'filter', 'filter' => 'array_values'],
                ];
            }

            public function getFieldOptions(): array
            {
                return ['methods' => ['card' => 'Card', 'bank' => 'Bank transfer', 'wallet' => 'Wallet']];
            }
        };
    }

    public function testSerializesArraySettings(): void
    {
        $serializer = new class {
            use ModuleSerializerTrait {
                serializeModule as public;
            }
        };

        $this->assertSame([
            'key' => 'payment',
            'label' => 'Payment Settings',
            'schema' => [
                '$schema' => 'http://json-schema.org/draft-07/schema#',
                'title' => 'Payment',
                'type' => 'object',
                'properties' => [
                    'methods' => [
                        'title' => 'Methods',
                        'type' => 'array',
                        'uniqueItems' => true,
                        'items' => ['oneOf' => [
                            ['const' => 'card', 'title' => 'Card'],
                            ['const' => 'bank', 'title' => 'Bank transfer'],
                            ['const' => 'wallet', 'title' => 'Wallet'],
                        ]],
                    ],
                    'blockedReferrers' => [
                        'title' => 'Blocked Referrers',
                        'type' => 'array',
                        'items' => ['type' => 'string', 'maxLength' => 255],
                    ],
                ],
            ],
            'values' => ['methods' => ['card'], 'blockedReferrers' => []],
        ], $serializer->serializeModule($this->module));
    }

    public function testFiltersKeepListsAList(): void
    {
        $this->module->setAttributes([
            'methods' => ['bank', 'card', 'bank'],
            'blockedReferrers' => [2 => 'b.example', 5 => 'a.example'],
        ]);

        $this->assertTrue($this->module->validate());
        $this->assertSame(['bank', 'card'], $this->module->methods);
        $this->assertSame(['b.example', 'a.example'], $this->module->blockedReferrers);
    }

    public function testAcceptsEmptyArrays(): void
    {
        $this->module->setAttributes(['methods' => [], 'blockedReferrers' => []]);

        $this->assertTrue($this->module->validate());
        $this->assertSame([], $this->module->methods);
        $this->assertSame([], $this->module->blockedReferrers);
    }

    public function testRejectsValueOutsideOptions(): void
    {
        $this->module->setAttributes(['methods' => ['card', 'cash']]);

        $this->assertFalse($this->module->validate());
        $this->assertSame(['Methods is invalid.'], $this->module->getErrors('methods'));
    }
}
