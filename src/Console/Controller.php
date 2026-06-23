<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Console;

use Horat1us\Yii\Configurator\Registry;
use Horat1us\Yii\Configurator\Repository;
use yii\console;
use yii\helpers\Console as YiiConsole;

/**
 * Manage configurator module settings from the command line.
 *
 * Commands:
 *   yii configurator/list               - List all registered modules with their current values
 *   yii configurator/get <key>          - Show all attributes of a single module
 *   yii configurator/set <key> <attr> <value> - Set a single attribute value
 */
class Controller extends console\Controller
{
    public $defaultAction = 'list';

    /**
     * Lists all registered configurator modules and their current values.
     */
    public function actionList(): int
    {
        $registry   = \Yii::$container->get(Registry::class);
        $repository = \Yii::$container->get(Repository::class);

        $modules = $registry->all();
        if (empty($modules)) {
            $this->stdout("No modules registered.\n", YiiConsole::FG_YELLOW);
            return console\ExitCode::OK;
        }

        foreach ($modules as $module) {
            $repository->loadModule($module);
            $this->stdout(
                "\n[{$module->getKey()}] {$module->getModuleLabel()}\n",
                YiiConsole::FG_GREEN,
                YiiConsole::BOLD,
            );
            foreach ($module->defaults() as $attr => $default) {
                $value = $module->$attr;
                $this->stdout("  {$attr}: ", YiiConsole::FG_CYAN);
                $this->stdout(var_export($value, true) . "\n");
            }
        }

        $this->stdout("\n");
        return console\ExitCode::OK;
    }

    /**
     * Shows all attributes of a single module.
     *
     * @param string $key Module key (e.g. "payment")
     */
    public function actionGet(string $key): int
    {
        $registry = \Yii::$container->get(Registry::class);

        if (!$registry->has($key)) {
            $this->stderr("Unknown module: {$key}\n", YiiConsole::FG_RED);
            return console\ExitCode::DATAERR;
        }

        $module = $registry->get($key);
        \Yii::$container->get(Repository::class)->loadModule($module);

        $this->stdout("\n[{$module->getKey()}] {$module->getModuleLabel()}\n", YiiConsole::FG_GREEN, YiiConsole::BOLD);
        foreach ($module->defaults() as $attr => $default) {
            $value = $module->$attr;
            $this->stdout("  {$attr}: ", YiiConsole::FG_CYAN);
            $this->stdout(var_export($value, true));
            $this->stdout(" (default: " . var_export($default, true) . ")\n", YiiConsole::FG_GREY);
        }
        $this->stdout("\n");

        return console\ExitCode::OK;
    }

    /**
     * Sets a single attribute of a module.
     *
     * @param string $key   Module key (e.g. "payment")
     * @param string $attr  Attribute name (e.g. "enabled")
     * @param string $value New value as a string (booleans: "1"/"0", integers: numeric string)
     */
    public function actionSet(string $key, string $attr, string $value): int
    {
        $registry = \Yii::$container->get(Registry::class);

        if (!$registry->has($key)) {
            $this->stderr("Unknown module: {$key}\n", YiiConsole::FG_RED);
            return console\ExitCode::DATAERR;
        }

        $module = $registry->get($key);
        if (!array_key_exists($attr, $module->defaults())) {
            $this->stderr("Unknown attribute '{$attr}' for module '{$key}'\n", YiiConsole::FG_RED);
            return console\ExitCode::DATAERR;
        }

        \Yii::$container->get(Repository::class)->set($key, $attr, $value);

        $this->stdout("Set {$key}.{$attr} = {$value}\n", YiiConsole::FG_GREEN);

        return console\ExitCode::OK;
    }
}
