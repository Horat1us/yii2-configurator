<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit;

use Horat1us\Yii\Configurator\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new class extends Module {
            public string $title = 'Hello';
            public bool $enabled = true;
            public int $timeout = 30;

            public function getKey(): string { return 'demo'; }
            public function getModuleLabel(): string { return 'Demo Module'; }
            public function defaults(): array {
                return ['title' => 'Hello', 'enabled' => true, 'timeout' => 30];
            }
            public function attributeDescriptions(): array {
                return ['timeout' => 'Request timeout in seconds'];
            }
            public function getFieldOptions(): array {
                return ['enabled' => [0 => 'Disabled', 1 => 'Enabled']];
            }
            public function rules(): array {
                return [
                    [['title'], 'string', 'max' => 255],
                    [['enabled'], 'boolean'],
                    [['timeout'], 'integer', 'min' => 1],
                ];
            }
        };
    }

    public function testFormNameReturnsKey(): void
    {
        $this->assertSame('demo', $this->module->formName());
    }

    public function testAttributeHintsDelegateToDescriptions(): void
    {
        $hints = $this->module->attributeHints();
        $this->assertSame('Request timeout in seconds', $hints['timeout']);
    }

    public function testAttributeValuesLabelsReturnsMappingForKnownAttribute(): void
    {
        $labels = $this->module->attributeValuesLabels('enabled');
        $this->assertSame([0 => 'Disabled', 1 => 'Enabled'], $labels);
    }

    public function testAttributeValuesLabelsReturnsNullForUnknownAttribute(): void
    {
        $this->assertNull($this->module->attributeValuesLabels('title'));
    }

    public function testGetRequiredPermissionReturnsNullByDefault(): void
    {
        $this->assertNull($this->module->getRequiredPermission());
    }
}
