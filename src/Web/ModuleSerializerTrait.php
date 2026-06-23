<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\JsonSchema;

trait ModuleSerializerTrait
{
    protected function serializeModule(Module $module): array
    {
        return [
            'key'    => $module->getKey(),
            'label'  => $module->getModuleLabel(),
            'schema' => (new JsonSchema($module))->jsonSerialize(),
            'values' => $module->getAttributes(array_keys($module->defaults())),
        ];
    }
}
