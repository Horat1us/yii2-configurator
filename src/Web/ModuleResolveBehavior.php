<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Registry;
use yii\base\Behavior;
use yii\base\Model;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Resolves the `key` GET param to a Module instance and enforces per-module permissions.
 *
 * Must be registered AFTER GetParamsBehavior in behaviors() so that `key` is populated
 * before this handler fires (both use EVENT_BEFORE_VALIDATE; Yii2 fires in registration order).
 *
 * @property-read Model $owner
 */
class ModuleResolveBehavior extends Behavior
{
    public function events(): array
    {
        return [Model::EVENT_BEFORE_VALIDATE => 'resolveModule'];
    }

    public function resolveModule(): void
    {
        $key = $this->owner->key ?? null;
        if (empty($key)) {
            return;
        }

        $registry = \Yii::$container->get(Registry::class);

        if (!$registry->has($key)) {
            throw new NotFoundHttpException("Unknown configurator module: {$key}");
        }

        $module = $registry->get($key);
        $this->checkPermission($module);

        $this->owner->module = $module;
    }

    private function checkPermission(Module $module): void
    {
        $permission = $module->getRequiredPermission();
        if ($permission !== null && !\Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException();
        }
    }
}
