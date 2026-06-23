<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use yii\base;
use yii\web;

class Bootstrap implements base\BootstrapInterface
{
    public string $moduleName = 'staff';
    public string $controllerMapKey = 'configurator';

    public function bootstrap($app): void
    {
        if (!$app instanceof web\Application) {
            return;
        }

        $app->getModule($this->moduleName, true)
            ->controllerMap[$this->controllerMapKey] = [
                'class' => Controller::class,
            ];
    }
}
