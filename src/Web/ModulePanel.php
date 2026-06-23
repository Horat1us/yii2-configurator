<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Repository;
use Wearesho\Yii\Http;

class ModulePanel extends Http\Panel
{
    use ModuleSerializerTrait;

    public ?string $key = null;
    public ?Module $module = null;

    public function behaviors(): array
    {
        return [
            'getParams'     => [
                'class'      => Http\Behaviors\GetParamsBehavior::class,
                'attributes' => ['key'],
            ],
            'resolveModule' => [
                'class' => ModuleResolveBehavior::class,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['key'], 'required'],
            [['key'], 'string'],
        ];
    }

    protected function generateResponse(): array
    {
        \Yii::$container->get(Repository::class)->loadModule($this->module);
        return $this->serializeModule($this->module);
    }
}
