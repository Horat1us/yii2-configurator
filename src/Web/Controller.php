<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Wearesho\Yii\Http;
use yii\filters\AccessControl;
use yii\filters\AccessRule;

class Controller extends Http\Controller
{
    public $defaultAction = 'modules';

    /**
     * When set, access is restricted to users with this RBAC permission.
     * Set to null to allow any authenticated user.
     */
    public ?string $managePermission = null;

    public function actions(): array
    {
        return [
            'modules' => ['get' => ModulesPanel::class],
            'module'  => [
                'get' => ModulePanel::class,
                'put' => ModuleForm::class,
            ],
            'history' => ['get' => HistoryPanel::class],
        ];
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        if ($this->managePermission !== null) {
            $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'class'       => AccessRule::class,
                        'allow'       => true,
                        'permissions' => [$this->managePermission],
                    ],
                ],
            ];
        }

        return $behaviors;
    }
}
