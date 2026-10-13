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
            // replace web bootstrap reference: adding another element would bootstrap web twice
            $web = [
                'class' => WebBootstrap::class,
                'managePermission' => $this->managePermission,
            ];
            $index = array_search(WebBootstrap::class, $this->chain, true);
            if ($index === false) {
                $this->chain[] = $web;
            } else {
                $this->chain[$index] = $web;
            }
        }

        parent::bootstrap($app);
    }
}
