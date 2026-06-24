<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Integration;

use Horat1us\Yii\Configurator\Module;

/**
 * Concrete module used for DI-based tests (getModule / getValue).
 * Must be a named class so the DI container can instantiate it without arguments.
 */
class TestModule extends Module
{
    public string $title = 'Default Title';
    public bool $active = false;
    public int $limit = 10;

    public function getKey(): string
    {
        return 'test';
    }
    public function getModuleLabel(): string
    {
        return 'Test Module';
    }
    public function defaults(): array
    {
        return ['title' => 'Default Title', 'active' => false, 'limit' => 10];
    }
    public function rules(): array
    {
        return [
            [['title'], 'string', 'max' => 255],
            [['active'], 'boolean'],
            [['limit'], 'integer'],
        ];
    }
}
