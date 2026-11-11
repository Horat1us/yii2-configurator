<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Unit;

use Horat1us\Yii\Configurator\ListFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListFilterTest extends TestCase
{
    /**
     * @return iterable<string, array{ListFilter, mixed, mixed}>
     */
    public static function values(): iterable
    {
        $filter = new ListFilter();

        yield 'trims strings' => [$filter, [' a ', "b\n"], ['a', 'b']];
        yield 'drops empty strings after trimming' => [$filter, ['a', '', '  ', 'b'], ['a', 'b']];
        yield 'drops repeated values, keeping the first' => [$filter, ['b', 'a', ' b', 'a'], ['b', 'a']];
        yield 'compares strictly' => [$filter, [1, '1', '10', '1e1', true], [1, '1', '10', '1e1', true]];
        yield 're-indexes gaps' => [$filter, [2 => 'a', 5 => 'b'], ['a', 'b']];
        yield 're-indexes string keys' => [$filter, ['x' => 'a', 'y' => 'b'], ['a', 'b']];
        yield 'keeps values that are not strings' => [
            $filter,
            [' a', 5, null, ['b'], false],
            ['a', 5, null, ['b'], false],
        ];
        yield 'empty list' => [$filter, [], []];
        yield 'not an array: unchanged' => [$filter, 'a, b', 'a, b'];
        yield 'null: unchanged' => [$filter, null, null];

        yield 'trim off' => [new ListFilter(trim: false), [' a ', '', 'a'], [' a ', 'a']];
        yield 'removeEmpty off' => [new ListFilter(removeEmpty: false), ['a', ' ', ''], ['a', '']];
        yield 'unique off' => [new ListFilter(unique: false), ['a', ' a', 'a'], ['a', 'a', 'a']];
        yield 'everything off still re-indexes' => [
            new ListFilter(trim: false, removeEmpty: false, unique: false),
            [3 => ' a', 7 => '', 9 => ' a'],
            [' a', '', ' a'],
        ];
    }

    #[DataProvider('values')]
    public function testFilters(ListFilter $filter, mixed $value, mixed $expected): void
    {
        $this->assertSame($expected, $filter($value));
    }
}
