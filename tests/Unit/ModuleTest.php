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

            public function getKey(): string
            {
                return 'demo';
            }
            public function getModuleLabel(): string
            {
                return 'Demo Module';
            }
            public function defaults(): array
            {
                return ['title' => 'Hello', 'enabled' => true, 'timeout' => 30];
            }
            public function attributeDescriptions(): array
            {
                return ['timeout' => 'Request timeout in seconds'];
            }
            public function getFieldOptions(): array
            {
                return ['enabled' => ['disabled' => 'Disabled', 'enabled' => 'Enabled']];
            }
            public function rules(): array
            {
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
        $this->assertSame(['disabled' => 'Disabled', 'enabled' => 'Enabled'], $labels);
    }

    public function testAttributeValuesLabelsReturnsNullForUnknownAttribute(): void
    {
        $this->assertNull($this->module->attributeValuesLabels('title'));
    }

    public function testGetRequiredPermissionReturnsNullByDefault(): void
    {
        $this->assertNull($this->module->getRequiredPermission());
    }

    public function testSetAttributesCoercesValuesLikeModel(): void
    {
        $this->module->setAttributes(['timeout' => '15', 'enabled' => '0', 'title' => 42]);

        $this->assertSame(15, $this->module->timeout);
        $this->assertFalse($this->module->enabled);
        $this->assertSame('42', $this->module->title);
        $this->assertTrue($this->module->validate());
    }

    public function testSetAttributesReportsEmptyStringForIntAsBlank(): void
    {
        $this->module->setAttributes(['title' => 'Updated', 'timeout' => '']);

        $this->assertSame('Updated', $this->module->title, 'other attributes are still assigned');
        $this->assertSame(30, $this->module->timeout, 'invalid value is not assigned');
        $this->assertFalse($this->module->validate());
        $this->assertSame(['timeout' => ['Timeout cannot be blank.']], $this->module->getErrors());
    }

    public function testSetAttributesReportsNonNumericStringForInt(): void
    {
        $this->module->setAttributes(['timeout' => 'abc']);

        $this->assertFalse($this->module->validate());
        $this->assertSame(['Timeout must be an integer.'], $this->module->getErrors('timeout'));
    }

    public function testSetAttributesReportsArrayForString(): void
    {
        $this->module->setAttributes(['title' => ['a']]);

        $this->assertFalse($this->module->validate());
        $this->assertSame(['Title must be a string.'], $this->module->getErrors('title'));
    }

    public function testSetAttributesReportsNullForBoolAsBlank(): void
    {
        $this->module->setAttributes(['enabled' => null]);

        $this->assertFalse($this->module->validate());
        $this->assertSame(['Enabled cannot be blank.'], $this->module->getErrors('enabled'));
    }

    public function testSetAttributesForgetsPreviousAssignmentErrors(): void
    {
        $this->module->setAttributes(['timeout' => '']);
        $this->module->setAttributes(['timeout' => 5]);

        $this->assertTrue($this->module->validate());
        $this->assertSame(5, $this->module->timeout);
    }
}
