---
name: add-configurator-module
description: Creates a new configurator module for a domain feature. Use when a domain module needs runtime-configurable settings that can be changed via the admin UI or CLI without a deployment.
---

# Add a Configurator Module

A configurator module is a Yii2 Model subclass whose typed public properties represent runtime settings stored in the database. The Repository loads and saves them; the Web API and CLI expose them to operators.

---

## Checklist

- [ ] Create `{Domain}/Configurator/Module.php` extending `\Horat1us\Yii\Configurator\Module`
- [ ] Define typed public properties for every setting
- [ ] Implement `getKey()`, `getModuleLabel()`, `defaults()`, `rules()`
- [ ] Add `attributeLabels()`, `attributeDescriptions()`, `getFieldOptions()` as needed
- [ ] Register the module in the domain Bootstrap
- [ ] Add a translation message file for each supported locale
- [ ] Register the i18n source in the app bootstrap
- [ ] Update call sites to use one of the three access patterns

---

## Step 1 — Create the Module class

Place in `src/{Domain}/Configurator/Module.php`:

```php
namespace MyApp\{Domain}\Configurator;

use Horat1us\Yii\Configurator\Module;

class {Domain}Module extends Module
{
    // Typed public properties. PHP type determines DB→PHP cast:
    //   bool  → (bool)(int)   (store "1"/"0")
    //   int   → (int)
    //   string → string (default)
    public bool $enabled = true;
    public string $mode = 'standard';
    public int $limit = 100;

    public function getKey(): string
    {
        return '{domainKey}';  // camelCase, e.g. 'payment', 'bankId'
    }

    public function getModuleLabel(): string
    {
        return \Yii::t('{domain}', '{Domain} Settings');
    }

    public function defaults(): array
    {
        // Must list EVERY managed property. Used as fallback when DB has no entry.
        return [
            'enabled' => true,
            'mode'    => 'standard',
            'limit'   => 100,
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'enabled' => \Yii::t('{domain}', 'Enabled'),
            'mode'    => \Yii::t('{domain}', 'Mode'),
            'limit'   => \Yii::t('{domain}', 'Limit'),
        ];
    }

    public function attributeDescriptions(): array
    {
        // Shown as field hints in the UI.
        return [
            'limit' => \Yii::t('{domain}', 'Maximum number of items to process per run.'),
        ];
    }

    public function getFieldOptions(): array
    {
        // For string fields validated with 'in'. Each key must match a rules() 'range' value.
        return [
            'mode' => [
                'standard' => \Yii::t('{domain}', 'Standard'),
                'fast'     => \Yii::t('{domain}', 'Fast'),
                'safe'     => \Yii::t('{domain}', 'Safe'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['enabled'], 'boolean'],
            [['mode'], 'in', 'range' => ['standard', 'fast', 'safe']],
            [['limit'], 'integer', 'min' => 1, 'max' => 10000],
        ];
    }
}
```

### Field type→validator mapping

| PHP type | Yii2 rule      | JSON Schema output               |
|----------|----------------|----------------------------------|
| `bool`   | `boolean`      | `{"type":"boolean"}`             |
| `int`    | `integer`      | `{"type":"integer"}`             |
| `string` | `string`       | `{"type":"string"}`              |
| `string` | `string`, max>255 | `{"type":"string","format":"textarea"}` |
| `string` | `url`          | `{"type":"string","format":"uri"}` |
| `string` | `in`, `range` + `getFieldOptions()` | `{"type":"string","oneOf":[...]}` |

---

## Step 2 — Register in domain Bootstrap

```php
use Horat1us\Yii\Configurator\Registry;
use MyApp\{Domain}\Configurator\{Domain}Module;

// In your domain Bootstrap::bootstrap():
\Yii::$container->get(Registry::class)
    ->register(\Yii::$container->get({Domain}Module::class));
```

---

## Step 3 — Add translations

Create message files for every locale your application supports.

File: `src/{Domain}/messages/en/{domain}.php`

```php
return [
    '{Domain} Settings' => '{Domain} Settings',
    'Enabled'           => 'Enabled',
    'Mode'              => 'Mode',
    'Limit'             => 'Limit',
    'Standard'          => 'Standard',
    'Fast'              => 'Fast',
    'Safe'              => 'Safe',
    'Maximum number of items to process per run.' => 'Maximum number of items to process per run.',
];
```

File: `src/{Domain}/messages/uk/{domain}.php` — Ukrainian translations.

### Register the i18n source

In your app's web (and console) bootstrap, **before** the Registry registers the module:

```php
\Yii::$app->i18n->translations['{domain}'] = [
    'class'    => \yii\i18n\PhpMessageSource::class,
    'basePath' => '@app/{domain}/messages',
    'fileMap'  => ['{domain}' => '{domain}.php'],
];
```

> The package itself ships **no** translations. Every consuming project owns its own strings. This keeps the package free of locale assumptions and allows different apps to use different terminology.

---

## Step 4 — Use the module at call sites

Choose the pattern that fits the context:

### Option A — `getModule()`: multiple attributes from one module

Best for services that need several values in one place.

```php
readonly class SomeService
{
    public function __construct(
        private Repository $configurator,
    ) {}

    public function doWork(): void
    {
        $module = $this->configurator->getModule({Domain}Module::class);
        $mode  = $module->mode;
        $limit = $module->limit;
        // ... use both
    }
}
```

### Option B — `getValue()`: single attribute, minimal boilerplate

Best for bootstraps, behaviors, generators, and one-off reads.

```php
$enabled = \Yii::$container->get(Repository::class)
    ->getValue({Domain}Module::class, fn(Module $m) => $m->enabled);
```

### Option C — Inject module+repository into a Config class

Best for domain Config classes with typed getters and setters that keep business logic in one place.

```php
class Config
{
    public function __construct(
        private readonly {Domain}Module $module,
        private readonly Repository $repository,
    ) {
        $repository->loadModule($module);
    }

    public function isEnabled(): bool { return $this->module->enabled; }

    public function setEnabled(bool $value): void
    {
        $this->repository->set('{domainKey}', 'enabled', (string)(int)$value);
        $this->module->enabled = $value;
    }

    public function getMode(): string { return $this->module->mode; }

    public function setMode(string $mode): void
    {
        $this->repository->set('{domainKey}', 'mode', $mode);
        $this->module->mode = $mode;
    }
}
```

---

## Step 5 — Verify via CLI

```bash
# List all registered modules
php yii configurator/list

# Inspect the new module
php yii configurator/get {domainKey}

# Set a value
php yii configurator/set {domainKey} enabled 0
```
