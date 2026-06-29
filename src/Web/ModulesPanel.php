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

        $modules = array_filter(
            $registry->all(),
            static function ($module): bool {
                $permission = $module->getRequiredPermission();
                return $permission === null || \Yii::$app->user->can($permission);
            },
        );

        foreach ($modules as $module) {
            $repository->loadModule($module);
        }

        return [
            'modules' => array_map($this->serializeModule(...), $modules),
        ];
    }
}
