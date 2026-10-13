<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Registry;
use Horat1us\Yii\Configurator\Repository;
use Wearesho\Yii\Http;

class ModulesPanel extends Http\Panel
{
    use ModuleSerializerTrait;

    protected function generateResponse(): array
    {
        $registry   = \Yii::$container->get(Registry::class);
        $repository = \Yii::$container->get(Repository::class);

        // array_values: filtered out keys would turn the list into JSON object ({"1": ...})
        $modules = array_values(array_filter(
            $registry->all(),
            static function ($module): bool {
                $permission = $module->getRequiredPermission();
                return $permission === null || \Yii::$app->user->can($permission);
            },
        ));

        foreach ($modules as $module) {
            $repository->loadModule($module);
        }

        return [
            'modules' => array_map($this->serializeModule(...), $modules),
        ];
    }
}
