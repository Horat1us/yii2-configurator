<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator\Tests\Integration;

use Horat1us\Yii\Configurator\Records\Entry;
use Horat1us\Yii\Configurator\Records\History;
use Horat1us\Yii\Configurator\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    private Repository $repository;
    private TestModule $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new Repository();
        $this->module = new TestModule();
    }

    public function testLoadModuleUsesDefaultsWhenNoDbEntries(): void
    {
        $this->repository->loadModule($this->module);

        $this->assertSame('Default Title', $this->module->title);
        $this->assertFalse($this->module->active);
        $this->assertSame(10, $this->module->limit);
    }

    public function testSetAndGetRoundTrip(): void
    {
        $this->repository->set('test', 'title', 'Updated Title');
        $value = $this->repository->get('test', 'title');

        $this->assertSame('Updated Title', $value);
    }

    public function testSetCreatesHistoryEntry(): void
    {
        $this->repository->set('test', 'title', 'Hello', 42);

        $count = History::find()->andWhere(['key' => 'test.title'])->count();
        $this->assertSame(1, (int)$count);

        $record = History::find()->andWhere(['key' => 'test.title'])->one();
        $this->assertSame('Hello', $record->value);
        $this->assertSame(42, $record->user_id);
    }

    public function testSetUpsertsEntry(): void
    {
        $this->repository->set('test', 'title', 'First');
        $this->repository->set('test', 'title', 'Second');

        $count = Entry::find()->andWhere(['key' => 'test.title'])->count();
        $this->assertSame(1, (int)$count);

        $historyCount = History::find()->andWhere(['key' => 'test.title'])->count();
        $this->assertSame(2, (int)$historyCount);
    }

    public function testLoadModuleReadsFromDb(): void
    {
        $this->repository->set('test', 'title', 'From DB');
        $this->repository->set('test', 'active', '1');
        $this->repository->set('test', 'limit', '99');

        $this->repository->loadModule($this->module);

        $this->assertSame('From DB', $this->module->title);
        $this->assertTrue($this->module->active);
        $this->assertSame(99, $this->module->limit);
    }

    public function testSaveModuleWritesAllAttrsAtomically(): void
    {
        $this->module->title  = 'Bulk Save';
        $this->module->active = true;
        $this->module->limit  = 5;

        $this->repository->saveModule($this->module, 7);

        $this->repository->loadModule($this->module);
        $this->assertSame('Bulk Save', $this->module->title);
        $this->assertTrue($this->module->active);
        $this->assertSame(5, $this->module->limit);

        $historyCount = History::find()
            ->andWhere(['in', 'key', ['test.title', 'test.active', 'test.limit']])
            ->count();
        $this->assertSame(3, (int)$historyCount);
    }

    public function testGetModuleHistoryReturnsPaginatedResults(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->set('test', 'title', "Value {$i}");
        }

        $result = $this->repository->getModuleHistory($this->module, page: 1, perPage: 3);

        $this->assertSame(5, $result['total']);
        $this->assertCount(3, $result['items']);
    }

    public function testGetModuleReturnsTypedInstance(): void
    {
        $this->repository->set('test', 'limit', '77');

        $loaded = $this->repository->getModule(TestModule::class);
        $this->assertSame(77, $loaded->limit);
    }

    public function testGetValueExtractsSingleAttribute(): void
    {
        $this->repository->set('test', 'active', '1');

        $active = $this->repository->getValue(TestModule::class, fn($m) => $m->active);
        $this->assertTrue($active);
    }
}
