<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

use Horat1us\Yii\Model\AttributeValuesLabels;
use yii\base\Model;

/**
 * Base class for all Configurator modules.
 *
 * Each domain subclass declares typed public properties for its config fields.
 * Those properties are loaded from and saved to the configurator_entry table by Repository.
 *
 * Validation uses standard Yii2 rules():
 *   - bool fields: 'boolean' rule
 *   - int fields: 'integer' rule
 *   - URL string fields: 'url' rule  →  schema format: "uri"
 *   - large string fields: 'string', 'max' => N (N > 255)  →  schema format: "textarea"
 *   - select fields: 'in', 'range' => [...]  +  getFieldOptions() entry  →  schema oneOf with labels
 *
 * Translations: implement getModuleLabel() and attributeLabels() / attributeDescriptions() using
 * Yii::t() with your own i18n category. The package ships no translations.
 */
abstract class Module extends Model implements AttributeValuesLabels
{
    /**
     * Unique camelCase identifier for this module (e.g. 'payment', 'bankId').
     * Used as the URL segment and DB key prefix.
     */
    abstract public function getKey(): string;

    /**
     * Human-readable label for the module, already translated.
     */
    abstract public function getModuleLabel(): string;

    /**
     * Default values for every managed attribute.
     * Keys must exactly match the typed public properties.
     *
     * @return array<string, mixed>
     */
    abstract public function defaults(): array;

    /**
     * Human-readable descriptions shown as field hints in the UI, already translated.
     *
     * @return array<string, string>
     */
    public function attributeDescriptions(): array
    {
        return [];
    }

    /**
     * Options for select fields: attr => [value => label].
     * Must correspond to a RangeValidator range in rules() for the correct JSON Schema oneOf output.
     *
     * @return array<string, array<string, string>>
     */
    public function getFieldOptions(): array
    {
        return [];
    }

    public function attributeValuesLabels(string $attribute): ?array
    {
        return $this->getFieldOptions()[$attribute] ?? null;
    }

    public function attributeHints(): array
    {
        return $this->attributeDescriptions();
    }

    /**
     * Returns getKey() so the JSON Schema title is the module key, not 'Module'.
     */
    public function formName(): string
    {
        return $this->getKey();
    }

    /**
     * Optional per-module RBAC permission required on top of the base manage permission.
     * Return null to rely solely on the controller-level permission.
     */
    public function getRequiredPermission(): ?string
    {
        return null;
    }
}
