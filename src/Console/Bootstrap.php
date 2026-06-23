<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Console;

use yii\base;
use yii\console;

class Bootstrap implements base\BootstrapInterface
{
    public string $controllerMapKey = 'configurator';

    public function bootstrap($app): void
    {
        if (!$app instanceof console\Application) {
            return;
        }

        $app->controllerMap[$this->controllerMapKey] = [
            'class' => Controller::class,
        ];
    }
}
