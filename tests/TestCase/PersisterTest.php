<?php
namespace Qobo\Duplicates\Filter;

use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\EndsWithFilter;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Finder;
use Qobo\Duplicates\Persister;
use Qobo\Duplicates\Rule;

class PersisterTest extends TestCase
{
    public $fixtures = [
        'plugin.Qobo/Duplicates.articles',
        'plugin.Qobo/Duplicates.duplicates'
    ];

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $filters = [
            new EndsWithFilter(['field' => 'title', 'length' => 3]),
            new EndsWithFilter(['field' => 'excerpt', 'length' => 1])
        ];
        $this->rule = new Rule('foobar', new FilterCollection(...$filters));
        $this->table = TableRegistry::getTableLocator()->get('Articles');
        $this->finder = new Finder($this->table, $this->rule);
        $this->resultSet = $this->finder->execute()[0];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->resultSet);
        unset($this->finder);
        unset($this->table);
        unset($this->rule);

        parent::tearDown();
    }

    public function testGetOriginal()
    {
        $persister = new Persister($this->table, $this->rule, $this->resultSet);

        $this->assertSame($this->resultSet->first(), $persister->getOriginal());
    }

    public function testIsOriginal()
    {
        $persister = new Persister($this->table, $this->rule, $this->resultSet);

        $this->assertTrue($persister->isOriginal($this->resultSet->first()));
        $this->assertFalse($persister->isOriginal($this->resultSet->skip(1)->first()));
    }

    public function testGetErrors()
    {
        $persister = new Persister($this->table, $this->rule, $this->resultSet);

        $this->assertEmpty($persister->getErrors());

        $persister->execute();
        $this->assertEmpty($persister->getErrors());
    }

    public function testIsPersisted()
    {
        $persister = new Persister($this->table, $this->rule, $this->resultSet);

        $this->assertFalse($persister->isPersisted($this->table->get('00000000-0000-0000-0000-000000000001')));
    }

    public function testExecute()
    {
        $persister = new Persister($this->table, $this->rule, $this->resultSet);

        $this->assertTrue($persister->execute());

        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $query = $table->find('all')
            ->where(['model' => 'Articles']);

        $this->assertFalse($query->isEmpty());

        $entity = $query->first();
        $this->assertSame('00000000-0000-0000-0000-000000000002', $entity->get('original_id'));
        $this->assertSame('00000000-0000-0000-0000-000000000003', $entity->get('duplicate_id'));
    }

    public function testExecuteWithAlreadyPersisted()
    {
        $persister = new Persister(
            $this->table,
            new Rule('byTitle', new FilterCollection(...$this->rule->getFilters())),
            $this->table->find()
                ->where(['id IN' => ['00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000003']])
                ->all()
        );

        $this->assertTrue($persister->execute());

        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $query = $table->find('all')
            ->where(['model' => 'Articles']);

        $this->assertFalse($query->isEmpty());

        $entity = $query->first();
        $this->assertSame('00000000-0000-0000-0000-000000000002', $entity->get('original_id'));
        $this->assertSame('00000000-0000-0000-0000-000000000003', $entity->get('duplicate_id'));
    }
}
