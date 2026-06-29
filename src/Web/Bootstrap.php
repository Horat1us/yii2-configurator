<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use yii\base;
use yii\web;

class Bootstrap implements base\BootstrapInterface
{
    public string $moduleName = 'staff';
    public string $controllerMapKey = 'configurator';
    public ?string $managePermission = null;

    public function bootstrap($app): void
    {
        if (!$app instanceof web\Application) {
            return;
        }

        $config = ['class' => Controller::class];
        if ($this->managePermission !== null) {
            $config['managePermission'] = $this->managePermission;
        }

        $app->getModule($this->moduleName, true)
            ->controllerMap[$this->controllerMapKey] = $config;
    }
}
