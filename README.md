# horat1us/yii2-configurator

Runtime configuration management for Yii2 applications. Stores per-module key-value settings in PostgreSQL with a full change history. Exposes a REST API and CLI commands for reading and writing values.

## Installation

```bash
composer require horat1us/yii2-configurator
```

## Quick Start

### 1. Register Bootstrap

Add to your `config/console.php` and `config/web.php`:

```php
'bootstrap' => [
    \Horat1us\Yii\Configurator\Bootstrap::class,
],
```

### 2. Run Migrations

```bash
php yii migrate
```

This creates `configurator_entry` and `configurator_history` tables.

> **Note on foreign keys**: The `user_id` column stores a plain integer with no FK. If you want referential integrity against your user table, add a FK in a separate migration:
>
> ```php
> $this->addForeignKey(
>     'fk_configurator_entry_user',
>     'configurator_entry', 'user_id',
>     'users', 'id',
>     'SET NULL', 'CASCADE',
> );
> ```

### 3. Create a Module

```php
namespace MyApp\Payment\Configurator;

use Horat1us\Yii\Configurator\Module;

class PaymentModule extends Module
{
    public bool $enabled = true;
    public string $gateway = 'stripe';
    public int $timeoutSeconds = 30;

    public function getKey(): string
    {
        return 'payment';
    }

    public function getModuleLabel(): string
    {
        return \Yii::t('payment', 'Payment Settings');
    }

    public function defaults(): array
    {
        return [
            'enabled'        => true,
            'gateway'        => 'stripe',
            'timeoutSeconds' => 30,
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'enabled'        => \Yii::t('payment', 'Enabled'),
            'gateway'        => \Yii::t('payment', 'Gateway'),
            'timeoutSeconds' => \Yii::t('payment', 'Timeout (seconds)'),
        ];
    }

    public function getFieldOptions(): array
    {
        return [
            'gateway' => [
                'stripe' => \Yii::t('payment', 'Stripe'),
                'paypal' => \Yii::t('payment', 'PayPal'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['enabled'], 'boolean'],
            [['gateway'], 'in', 'range' => ['stripe', 'paypal']],
            [['timeoutSeconds'], 'integer', 'min' => 1, 'max' => 300],
        ];
    }
}
```

### 4. Register the Module

In your domain bootstrap:

```php
\Yii::$container->get(\Horat1us\Yii\Configurator\Registry::class)
    ->register(\Yii::$container->get(PaymentModule::class));
```

### 5. Read Configuration

```php
// Option A: inject and read multiple attributes
$module = \Yii::$container->get(Repository::class)->getModule(PaymentModule::class);
$enabled = $module->enabled;
$gateway = $module->gateway;

// Option B: read a single attribute (minimal boilerplate)
$gateway = \Yii::$container->get(Repository::class)
    ->getValue(PaymentModule::class, fn($m) => $m->gateway);

// Option C: inject module+repository into a Config class (best for complex domain logic)
// Use a lazy load() guard — never call loadModule() in the constructor.
// The constructor runs at DI resolution time (bootstrap), before migrations exist.
class Config
{
    private bool $loaded = false;

    public function __construct(
        private readonly PaymentModule $module,
        private readonly Repository $repository,
    ) {
    }

    public function getGateway(): string { return $this->load()->gateway; }

    public function setGateway(string $gateway): void
    {
        $this->repository->set('payment', 'gateway', $gateway);
        $this->load()->gateway = $gateway;
    }

    private function load(): PaymentModule
    {
        if (!$this->loaded) {
            $this->repository->loadModule($this->module);
            $this->loaded = true;
        }
        return $this->module;
    }
}
```

---

## Translations

The package ships no translations. Each module handles its own i18n via standard Yii2 `\Yii::t()` calls.

To add translations for your module, register an i18n source in your bootstrap (web or console):

```php
\Yii::$app->i18n->translations['payment'] = [
    'class' => \yii\i18n\PhpMessageSource::class,
    'basePath' => '@app/messages',
    'fileMap'  => ['payment' => 'payment.php'],
];
```

Create the message files:

```
app/
  messages/
    en/
      payment.php
    uk/
      payment.php
```

Example `messages/en/payment.php`:

```php
return [
    'Payment Settings' => 'Payment Settings',
    'Enabled'          => 'Enabled',
    'Gateway'          => 'Gateway',
    'Timeout (seconds)' => 'Timeout (seconds)',
    'Stripe'           => 'Stripe',
    'PayPal'           => 'PayPal',
];
```

Use the same category string (`'payment'`) consistently across `getModuleLabel()`, `attributeLabels()`, `getFieldOptions()`, and `attributeDescriptions()`.

---

## Web API

The package registers these routes under the `staff` module (configurable via `Bootstrap::$moduleName`):

| Method | Path                          | Description                        |
|--------|-------------------------------|------------------------------------|
| GET    | `/staff/configurator/modules` | List all modules with schema       |
| GET    | `/staff/configurator/module`  | Get a single module (`?key=payment`) |
| PUT    | `/staff/configurator/module`  | Update a module (`?key=payment`)   |
| GET    | `/staff/configurator/history` | Change history (`?key=payment`)    |

To protect routes with an RBAC permission:

```php
// in your web config
'bootstrap' => [
    [
        'class' => \Horat1us\Yii\Configurator\Bootstrap::class,
        'chain' => [
            \Horat1us\Yii\Configurator\Migrations\Bootstrap::class,
            [
                'class'              => \Horat1us\Yii\Configurator\Web\Bootstrap::class,
                'moduleName'         => 'admin',
                'controllerMapKey'   => 'configurator',
            ],
            \Horat1us\Yii\Configurator\Console\Bootstrap::class,
        ],
    ],
],
```

Or set `managePermission` on the controller:

```php
$app->getModule('admin')->controllerMap['configurator'] = [
    'class'             => \Horat1us\Yii\Configurator\Web\Controller::class,
    'managePermission'  => 'manageConfigurator',
];
```

### Module response shape

```json
{
  "key": "payment",
  "label": "Payment Settings",
  "schema": {
    "$schema": "http://json-schema.org/draft-07/schema#",
    "title": "payment",
    "type": "object",
    "properties": {
      "enabled": { "type": "boolean" },
      "gateway": {
        "type": "string",
        "oneOf": [
          { "const": "stripe", "title": "Stripe" },
          { "const": "paypal", "title": "PayPal" }
        ]
      },
      "timeoutSeconds": { "type": "integer", "minimum": 1, "maximum": 300 }
    }
  },
  "values": {
    "enabled": true,
    "gateway": "stripe",
    "timeoutSeconds": 30
  }
}
```

---

## Frontend Integration

The API is designed to be fully self-describing: the `schema` field contains everything a frontend needs to render a settings form without any hardcoded field knowledge.

### Endpoint reference

| Method | Path                          | Query params          | Body                   |
|--------|-------------------------------|-----------------------|------------------------|
| GET    | `/staff/configurator/modules` | —                     | —                      |
| GET    | `/staff/configurator/module`  | `key=<moduleKey>`     | —                      |
| PUT    | `/staff/configurator/module`  | `key=<moduleKey>`     | JSON object of values  |
| GET    | `/staff/configurator/history` | `key`, `page`, `perPage` | —                   |

---

### Module object

Every module endpoint returns (or is an array of) this shape:

```jsonc
{
  "key": "payment",          // stable identifier, use as URL param
  "label": "Payment Settings", // human-readable, already translated, use as section heading
  "schema": { /* JSON Schema draft-07 — see below */ },
  "values": {                // current live values, typed
    "enabled": true,
    "gateway": "stripe",
    "timeoutSeconds": 30
  }
}
```

`values` keys correspond exactly to `schema.properties` keys. Use `values` to populate form fields on load. The types in `values` match the JSON Schema `type` declarations — `boolean`, `integer`, `number`, or `string`.

---

### JSON Schema structure

The `schema` object is a JSON Schema Draft-07 document describing all editable fields.

```jsonc
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "payment",            // equals the module key (camelCase words)
  "type": "object",
  "required": ["gateway"],       // fields with a 'required' validation rule
  "properties": {
    "fieldName": {
      "title": "Field Label",    // from attributeLabels(), already translated
      "description": "Hint",    // from attributeDescriptions(), present only when set
      "examples": [1, 2],        // present only when module implements AttributesExamples
      /* type constraint — see field types below */
    }
  }
}
```

`required` only lists fields that have an unconditional `required` validation rule. Fields absent from `required` are optional.

---

### Field types

The `type` key and optional `format`/`oneOf`/`enum` keys drive widget selection.

#### Boolean

```jsonc
{ "title": "Enabled", "type": "boolean" }
```

Render as a toggle/checkbox. Send `true` or `false` in the PUT body.

#### Integer

```jsonc
{ "title": "Timeout", "type": "integer", "minimum": 1, "maximum": 300 }
```

Render as a number input. `minimum`/`maximum` are present only when the validator has `min`/`max` set. Send as a JSON number without a decimal point.

#### Decimal number

```jsonc
{ "title": "Rate", "type": "number", "minimum": 0 }
```

Same as integer but allows fractions. Send as a JSON number.

#### Short string

```jsonc
{ "title": "Name", "type": "string", "maxLength": 255 }
```

Render as a single-line text input. `minLength`/`maxLength` are present only when the validator declares `min`/`max`.

#### Long string (textarea)

```jsonc
{ "title": "Description", "type": "string", "maxLength": 65535, "format": "textarea" }
```

`format: "textarea"` appears when `maxLength > 255`. Render as a multi-line textarea.

#### URL

```jsonc
{ "title": "Webhook URL", "type": "string", "format": "uri" }
```

Render as a URL input (`<input type="url">`). Validate with the browser's built-in URL parser before sending.

#### Email

```jsonc
{ "title": "Sender", "type": "string", "format": "email" }
```

Render as `<input type="email">`.

#### Date / time

```jsonc
{ "title": "Starts At", "type": "string", "format": "date" }
// or "format": "time" / "date-time"
```

Render as a date/time picker. Send as an ISO 8601 string.

#### Select with labels (oneOf)

```jsonc
{
  "title": "Gateway",
  "type": "string",
  "oneOf": [
    { "const": "stripe", "title": "Stripe" },
    { "const": "paypal", "title": "PayPal" }
  ]
}
```

Render as a `<select>`. Use `const` as the option value and `title` as the display label. Send the raw `const` string in the PUT body. `oneOf` appears when the module defines `getFieldOptions()` labels for every value in the `in` validator range.

#### Select without labels (enum)

```jsonc
{ "title": "Mode", "enum": ["standard", "fast", "safe"] }
```

Same render as `oneOf` but use the value itself as both value and label. `enum` appears when a `RangeValidator` has no matching `getFieldOptions()` entry.

#### String with pattern

```jsonc
{ "title": "Code", "type": "string", "pattern": "^[A-Z]{3}$" }
```

The `pattern` value is a JavaScript-compatible regex (delimiters stripped from the PHP pattern). Use `new RegExp(pattern).test(value)` for client-side validation.

---

### PUT request

Send a JSON object with the same keys as `values`. Include all fields (the server replaces all attributes atomically).

```http
PUT /staff/configurator/module?key=payment
Content-Type: application/json

{
  "enabled": false,
  "gateway": "paypal",
  "timeoutSeconds": 60
}
```

Type rules for the body:
- `type: "boolean"` → JSON `true`/`false`
- `type: "integer"` or `"number"` → JSON number
- `type: "string"` (all formats) → JSON string
- `oneOf`/`enum` → the raw `const` value as a JSON string

The server re-validates with the same rules and returns the saved module object (identical shape to the GET response). On validation failure the server returns HTTP 422 with a field-keyed error map.

---

### History endpoint

```http
GET /staff/configurator/history?key=payment&page=1&perPage=20
```

Response:

```jsonc
{
  "items": [
    {
      "key": "payment.gateway",      // module key + "." + attribute name
      "value": "paypal",             // always a string (stored representation)
      "createdAt": "2026-06-24 11:30:00",
      "user": { "id": 7, "name": "Alice" }  // shape depends on UserSerializerInterface
      // "user" is null when the change was made without an authenticated user
    }
  ],
  "pagination": {
    "total": 42,
    "page": 1,
    "perPage": 20,
    "pageCount": 3
  }
}
```

`key` in history items uses dot notation: `<moduleKey>.<attributeName>`. The `value` is always a raw string regardless of the field type. To display it meaningfully:
- boolean: `"1"` → true, `"0"` → false
- integer/number: parse with `Number(value)`
- `oneOf` fields: look up the matching `const` in `schema.properties[attr].oneOf` to get the human `title`

`perPage` is capped at 100 by the server regardless of what is requested.

---

### Rendering a settings form (pseudocode)

```js
async function loadModule(key) {
  const { schema, values } = await GET(`/staff/configurator/module?key=${key}`)
  const form = {}

  for (const [attr, property] of Object.entries(schema.properties)) {
    const value = values[attr]
    const required = schema.required?.includes(attr) ?? false
    form[attr] = buildField(property, value, required)
  }
  return form
}

function buildField(property, value, required) {
  if (property.oneOf) return { widget: 'select', options: property.oneOf, value, required }
  if (property.enum)  return { widget: 'select', options: property.enum.map(v => ({ const: v, title: v })), value, required }
  switch (property.type) {
    case 'boolean': return { widget: 'toggle', value, required }
    case 'integer':
    case 'number':  return { widget: 'number', min: property.minimum, max: property.maximum, value, required }
    case 'string':
      if (property.format === 'textarea') return { widget: 'textarea', maxLength: property.maxLength, value, required }
      if (property.format === 'uri')      return { widget: 'url',      value, required }
      if (property.format === 'email')    return { widget: 'email',    value, required }
      if (property.format === 'date')     return { widget: 'date',     value, required }
      if (property.format === 'date-time') return { widget: 'datetime', value, required }
      return { widget: 'text', maxLength: property.maxLength, value, required }
  }
}

async function saveModule(key, values) {
  return PUT(`/staff/configurator/module?key=${key}`, values)
}
```

---

## CLI Commands

```bash
# List all modules with current values
php yii configurator/list

# Show a single module
php yii configurator/get payment

# Set a value (strings only; booleans as "1"/"0")
php yii configurator/set payment gateway paypal
php yii configurator/set payment enabled 0
php yii configurator/set payment timeoutSeconds 60
```

---

## Custom User Serialization

By default, history entries include `"user": {"id": 42}`. To enrich this with user data from your project, bind `UserSerializerInterface` **before** bootstrapping:

```php
// In your application bootstrap, before Configurator's bootstrap runs:
\Yii::$container->set(
    \Horat1us\Yii\Configurator\UserSerializerInterface::class,
    \MyApp\Staff\UserSerializer::class,
);
```

```php
class UserSerializer implements \Horat1us\Yii\Configurator\UserSerializerInterface
{
    public function serialize(?int $userId): ?array
    {
        if ($userId === null) {
            return null;
        }
        $user = StaffUser::findOne($userId);
        return $user ? ['id' => $user->id, 'name' => $user->fullName] : ['id' => $userId];
    }
}
```

---

## Testing

### Unit tests (no DB required)

```bash
composer test:unit
```

### Integration tests (requires PostgreSQL)

Start the database:

```bash
docker-compose up -d
```

Run:

```bash
composer test:integration
```

Or with custom DB credentials:

```bash
DB_DSN="pgsql:host=127.0.0.1;port=5434;dbname=configurator_test" \
DB_USER=configurator \
DB_PASSWORD=configurator \
vendor/bin/phpunit --testsuite Integration
```
