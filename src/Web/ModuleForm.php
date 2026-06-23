<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Repository;
use Wearesho\Yii\Http;

class ModuleForm extends Http\Form
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
        $repository = \Yii::$container->get(Repository::class);
        $repository->loadModule($this->module);

        $this->module->setAttributes($this->request->bodyParams);
        Http\Exceptions\HttpValidationException::validateOrThrow($this->module);

        $userId = \Yii::$app->user->isGuest ? null : (int)\Yii::$app->user->id;
        $repository->saveModule($this->module, $userId);
        $repository->loadModule($this->module);

        return $this->serializeModule($this->module);
    }
}
