<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Records;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Append-only log of every configurator change.
 *
 * @property int    $id
 * @property string $key
 * @property string $value
 * @property int|null $user_id    Generic integer; no FK is created by this package's migration.
 *                                Consuming projects should add their own FK migration if needed.
 * @property string $created_at
 */
class History extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'configurator_history';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['key', 'value'], 'required'],
            [['key'], 'string', 'max' => 255],
            [['value'], 'string'],
            [['user_id'], 'integer'],
            [['user_id'], 'default', 'value' => null],
        ];
    }
}
