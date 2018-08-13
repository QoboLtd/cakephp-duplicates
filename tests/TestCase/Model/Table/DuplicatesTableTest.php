<?php
namespace Qobo\Duplicates\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Qobo\Duplicates\Model\Table\DuplicatesTable;

/**
 * Qobo\Duplicates\Model\Table\DuplicatesTable Test Case
 */
class DuplicatesTableTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.Qobo/Duplicates.articles',
        'plugin.Qobo/Duplicates.duplicates'
    ];

    /**
     * Test subject
     *
     * @var \Qobo\Duplicates\Model\Table\DuplicatesTable
     */
    public $Duplicates;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Duplicates = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Duplicates);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertInstanceOf(DuplicatesTable::class, $this->Duplicates);

        $this->assertSame('qobo_duplicates', $this->Duplicates->getTable());
        $this->assertSame('id', $this->Duplicates->getPrimaryKey());
        $this->assertSame('id', $this->Duplicates->getDisplayField());

        $this->assertTrue($this->Duplicates->hasBehavior('Timestamp'));

        $this->assertEmpty($this->Duplicates->associations()->keys());
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->assertInstanceOf(Validator::class, $this->Duplicates->validationDefault(new Validator()));
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->assertInstanceOf(RulesChecker::class, $this->Duplicates->buildRules(new RulesChecker()));
    }

    public function testSave()
    {
        $data = [
            'model' => 'Articles',
            'original_id' => '00000000-0000-0000-0000-000000000001',
            'duplicate_id' => '00000000-0000-0000-0000-000000000001',
            'rule' => 'byTitleName',
            'status' => 'pending'
        ];

        $entity = $this->Duplicates->newEntity();
        $entity = $this->Duplicates->patchEntity($entity, $data);

        $this->assertInstanceOf(EntityInterface::class, $this->Duplicates->save($entity));
        $this->assertEmpty(array_diff($data, $entity->toArray()));
    }

    public function testMapDuplicates()
    {
        // overwrite Utils plugin configuration
        Configure::write('CsvMigrations.modules.path', Configure::read('Duplicates.path'));

        $this->assertSame([], $this->Duplicates->mapDuplicates());
    }

    public function testFetchByModelAndRule()
    {
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [
                ['id' => '00000000-0000-0000-0000-000000000002', 'value' => 'Second Article', 'count' => 1]
            ]
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', []));
    }

    public function testFetchByModelAndRuleWithOptions()
    {
        $options = ['page' => 0, 'size' => 1];
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [
                ['id' => '00000000-0000-0000-0000-000000000002', 'value' => 'Second Article', 'count' => 1]
            ]
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', $options));

        $options = ['page' => 1, 'size' => 1];
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [] // page 1 has no data
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', $options));
    }

    public function testFetchByOriginalIDAndRule()
    {
        $result = $this->Duplicates->fetchByOriginalIDAndRule('00000000-0000-0000-0000-000000000002', 'byTitle');

        $this->assertInstanceOf(EntityInterface::class, $result['original']);
        $this->assertEquals(
            TableRegistry::getTableLocator()
                ->get('Articles')
                ->get('00000000-0000-0000-0000-000000000002'),
            $result['original']
        );

        $this->assertInstanceOf(ResultSetInterface::class, $result['duplicates']);
        $this->assertEquals(
            TableRegistry::getTableLocator()
                ->get('Articles')
                ->get('00000000-0000-0000-0000-000000000003'),
            $result['duplicates']->first()
        );

        $this->assertSame(['id', 'title', 'excerpt', 'body', 'created', 'modified'], $result['fields']);

        $this->assertSame([], $result['virtualFields']);
    }

    public function testFetchByOriginalIDAndRuleWithInvalidID()
    {
        $result = $this->Duplicates->fetchByOriginalIDAndRule('00000000-0000-0000-0000-000000000404', 'byTitle');

        $this->assertSame([], $result);
    }

    public function testDeleteDuplicates()
    {
        $ids = ['00000000-0000-0000-0000-000000000002'];

        $this->assertTrue($this->Duplicates->deleteDuplicates('Articles', $ids));

        $query = Tableregistry::getTableLocator()
            ->get('Articles')
            ->find('all')
            ->where(['id' => $ids[0]]);
        $this->assertTrue($query->isEmpty());

        $query = $this->Duplicates->find('all')
            ->where(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($query->isEmpty());
    }

    public function testDeleteDuplicatesWithInvalidID()
    {
        // get duplcicates count
        $count = $this->Duplicates->find('all')->count();
        $ids = [
            '00000000-0000-0000-0000-000000000001' // invalid duplicate ID
        ];

        $this->assertFalse($this->Duplicates->deleteDuplicates('Articles', $ids));

        // invalid duplicate ID
        $query = TableRegistry::getTableLocator()
            ->get('Articles')
            ->find('all')
            ->where(['id' => $ids[0]]);
        $this->assertFalse($query->isEmpty());

        // duplicate records were not affected
        $this->assertSame($count, $this->Duplicates->find('all')->count());
    }

    public function testFalsePositiveByRuleAndIDs()
    {
        $ids = ['00000000-0000-0000-0000-000000000003'];

        $this->assertTrue($this->Duplicates->falsePositiveByRuleAndIDs('byTitle', $ids));

        $entity = $this->Duplicates->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame('processed', $entity->get('status'));
    }

    public function testFalsePositiveByRuleAndIDsWithInvalidID()
    {
        $ids = ['00000000-0000-0000-0000-000000000404'];
        $resultSet = $this->Duplicates->find()->all();

        $this->assertFalse($this->Duplicates->falsePositiveByRuleAndIDs('byTitle', $ids));
        $this->assertEquals($resultSet, $this->Duplicates->find()->all());
    }

    public function testMergeDuplicates()
    {
        $id = '00000000-0000-0000-0000-000000000002';
        $data = ['excerpt' => 'Third'];

        $this->assertTrue($this->Duplicates->mergeDuplicates('Articles', $id, $data));
        $this->assertSame(
            $data['excerpt'],
            TableRegistry::getTableLocator()
                ->get('Articles')
                ->get($id)
                ->get('excerpt')
        );
    }

    public function testMergeDuplicatesWithInvalidID()
    {
        $id = '00000000-0000-0000-0000-000000000404';
        $data = ['excerpt' => 'Third'];

        $this->assertFalse($this->Duplicates->mergeDuplicates('Articles', $id, $data));
    }
}
