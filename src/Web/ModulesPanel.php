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

        $modules = $registry->all();
        foreach ($modules as $module) {
            $repository->loadModule($module);
        }

        return [
            'modules' => array_map($this->serializeModule(...), $modules),
        ];
    }
}
