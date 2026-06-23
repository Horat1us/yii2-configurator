<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Web;

use Horat1us\Yii\Configurator\Module;
use Horat1us\Yii\Configurator\Records\History;
use Horat1us\Yii\Configurator\Repository;
use Horat1us\Yii\Configurator\UserSerializerInterface;
use Wearesho\Yii\Http;

class HistoryPanel extends Http\Panel
{
    public ?string $key = null;
    public ?Module $module = null;
    public ?int $page = null;
    public ?int $perPage = null;

    public function behaviors(): array
    {
        return [
            'getParams'     => [
                'class'      => Http\Behaviors\GetParamsBehavior::class,
                'attributes' => ['key', 'page', 'perPage'],
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
            [['page', 'perPage'], 'integer', 'min' => 1],
            [['page'], 'default', 'value' => 1],
            [['perPage'], 'default', 'value' => 20],
            [['perPage'], 'filter', 'filter' => fn($v) => min((int)$v, 100)],
        ];
    }

    protected function generateResponse(): array
    {
        ['items' => $items, 'total' => $total] = \Yii::$container->get(Repository::class)
            ->getModuleHistory($this->module, $this->page, $this->perPage);

        $userSerializer = \Yii::$container->get(UserSerializerInterface::class);

        return [
            'items'      => array_map(
                fn(History $record) => $this->serializeHistoryItem($record, $userSerializer),
                $items,
            ),
            'pagination' => [
                'total'     => $total,
                'page'      => $this->page,
                'perPage'   => $this->perPage,
                'pageCount' => $total > 0 ? (int)ceil($total / $this->perPage) : 0,
            ],
        ];
    }

    private function serializeHistoryItem(History $record, UserSerializerInterface $userSerializer): array
    {
        return [
            'key'       => $record->key,
            'value'     => $record->value,
            'createdAt' => $record->created_at,
            'user'      => $userSerializer->serialize($record->user_id),
        ];
    }
}
