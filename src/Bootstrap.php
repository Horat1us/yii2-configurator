<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

use Horat1us\Yii\Chain\Bootstrap as ChainBootstrap;
use Horat1us\Yii\Configurator\Console\Bootstrap as ConsoleBootstrap;
use Horat1us\Yii\Configurator\Migrations\Bootstrap as MigrationsBootstrap;
use Horat1us\Yii\Configurator\Web\Bootstrap as WebBootstrap;

class Bootstrap extends ChainBootstrap
{
    public array $chain = [
        MigrationsBootstrap::class,
        WebBootstrap::class,
        ConsoleBootstrap::class,
    ];

    public ?string $managePermission = null;

    public function bootstrap($app): void
    {
        \Yii::$container->setSingleton(Registry::class);

        if (!\Yii::$container->has(UserSerializerInterface::class)) {
            \Yii::$container->set(UserSerializerInterface::class, DefaultUserSerializer::class);
        }

        if ($this->managePermission !== null) {
            $this->chain['web'] = [
                'class' => WebBootstrap::class,
                'managePermission' => $this->managePermission,
            ];
        }

        parent::bootstrap($app);
    }
}
