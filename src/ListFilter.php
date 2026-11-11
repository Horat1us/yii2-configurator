<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

/**
 * Callable for the `filter` rule of an array setting:
 *
 * ```php
 * [['blockedReferrers'], 'filter', 'filter' => new ListFilter()],
 * [['blockedReferrers'], 'each', 'rule' => ['string', 'max' => 255]],
 * ```
 *
 * - trims string values (`$trim`);
 * - drops empty strings, after trimming (`$removeEmpty`);
 * - drops repeated values, keeping the first one; values are compared strictly, so `1` and `"1"` both stay (`$unique`);
 * - always re-indexes the result to a list. An array with gaps in its keys
 *   (or an object sent by a client) would be stored and served as a JSON object.
 *
 * Values that aren't strings are kept as they are, so the rules after the filter (`each`, `in`) report them.
 * A value that isn't an array is returned unchanged.
 * Put the filter before the validation rules, so they check the cleaned list.
 */
final readonly class ListFilter
{
    public function __construct(
        public bool $trim = true,
        public bool $removeEmpty = true,
        public bool $unique = true,
    ) {
    }

    public function __invoke(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $list = [];
        foreach ($value as $item) {
            if ($this->trim && is_string($item)) {
                $item = trim($item);
            }
            if ($this->removeEmpty && $item === '') {
                continue;
            }
            if ($this->unique && in_array($item, $list, true)) {
                continue;
            }
            $list[] = $item;
        }
        return $list;
    }
}
