<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

use Horat1us\Yii\Configurator\Records\Entry;
use Horat1us\Yii\Configurator\Records\History;
use yii\db\Exception;

readonly class Repository
{
    /**
     * Instantiates the module class via DI, loads all its attributes from DB, returns it.
     * Best for reading multiple attributes from the same module in one DB round-trip.
     *
     * @template T of Module
     * @param class-string<T> $moduleClass
     * @return T
     */
    public function getModule(string $moduleClass): Module
    {
        /** @var T $module */
        $module = \Yii::$container->get($moduleClass);
        $this->loadModule($module);
        return $module;
    }

    /**
     * Instantiates the module, loads it, then calls the accessor to extract a single typed value.
     * Best for one-off reads in bootstraps, behaviors, or generators.
     *
     * @template T of Module
     * @template V
     * @param class-string<T> $moduleClass
     * @param \Closure(T): V $accessor
     * @return V
     */
    public function getValue(string $moduleClass, \Closure $accessor): mixed
    {
        /** @var T $module */
        $module = \Yii::$container->get($moduleClass);
        $this->loadModule($module);
        return $accessor($module);
    }

    /**
     * Loads all attributes for a module from DB in one IN(...) query.
     * Attributes absent from DB receive their default value. Values are cast to the PHP property type.
     */
    public function loadModule(Module $module): void
    {
        $attrs = array_keys($module->defaults());
        if (empty($attrs)) {
            return;
        }

        $dbKeys = array_map(fn(string $attr) => $this->dbKey($module->getKey(), $attr), $attrs);

        $entries = Entry::find()
            ->andWhere(['in', 'key', $dbKeys])
            ->indexBy('key')
            ->all();

        foreach ($attrs as $attr) {
            $dbKey = $this->dbKey($module->getKey(), $attr);
            $value = isset($entries[$dbKey])
                ? $entries[$dbKey]->value
                : (string)($module->defaults()[$attr] ?? '');

            $module->$attr = $this->castValue($module, $attr, $value);
        }
    }

    /**
     * Saves all module attributes atomically: upserts configurator_entry, appends configurator_history.
     *
     * @throws Exception on DB failure
     */
    public function saveModule(Module $module, ?int $userId = null): void
    {
        $attrs = array_keys($module->defaults());
        if (empty($attrs)) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $historyRows = [];
            foreach ($attrs as $attr) {
                $dbKey = $this->dbKey($module->getKey(), $attr);
                $value = (string)$module->$attr;

                $entry = Entry::findOne(['key' => $dbKey]) ?? new Entry(['key' => $dbKey]);
                $entry->value = $value;
                $entry->user_id = $userId;
                $entry->save(false);

                $historyRows[] = [$dbKey, $value, $now, $userId];
            }

            \Yii::$app->db->createCommand()->batchInsert(
                History::tableName(),
                ['key', 'value', 'created_at', 'user_id'],
                $historyRows,
            )->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new Exception("Failed to save module {$module->getKey()}", [], '0', $e);
        }
    }

    /**
     * Low-level single-attribute read. Returns the stored string value or $default.
     */
    public function get(string $moduleKey, string $attr, mixed $default = null): string
    {
        $entry = Entry::findOne(['key' => $this->dbKey($moduleKey, $attr)]);
        return $entry !== null ? $entry->value : (string)$default;
    }

    /**
     * Low-level single-attribute write. Upserts entry and appends one history row.
     */
    public function set(string $moduleKey, string $attr, string $value, ?int $userId = null): void
    {
        $dbKey = $this->dbKey($moduleKey, $attr);

        $entry = Entry::findOne(['key' => $dbKey]) ?? new Entry(['key' => $dbKey]);
        $entry->value = $value;
        $entry->user_id = $userId;
        $entry->save(false);

        $history = new History(['key' => $dbKey, 'value' => $value, 'user_id' => $userId]);
        $history->save(false);
    }

    /**
     * Returns paginated history records for all keys belonging to the given module.
     *
     * @return array{items: History[], total: int}
     */
    public function getModuleHistory(Module $module, int $page = 1, int $perPage = 20): array
    {
        $dbKeys = array_map(
            fn(string $attr) => $this->dbKey($module->getKey(), $attr),
            array_keys($module->defaults()),
        );

        $query = History::find()
            ->andWhere(['in', 'key', $dbKeys])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);

        $total = (int)$query->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->all();

        return compact('items', 'total');
    }

    private function dbKey(string $moduleKey, string $attr): string
    {
        return "{$moduleKey}.{$attr}";
    }

    private function castValue(Module $module, string $attr, string $value): mixed
    {
        $type = (new \ReflectionProperty($module, $attr))->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return $value;
        }
        return match ($type->getName()) {
            'bool' => (bool)(int)$value,
            'int'  => (int)$value,
            default => $value,
        };
    }
}
