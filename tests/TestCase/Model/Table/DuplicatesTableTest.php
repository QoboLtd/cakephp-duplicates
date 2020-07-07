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
        'plugin.CakeDC/Users.Users',
        'plugin.Qobo/Duplicates.Articles',
        'plugin.Qobo/Duplicates.Duplicates',
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

        /**
         * @var \Qobo\Duplicates\Model\Table\DuplicatesTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $this->Duplicates = $table;
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
    public function testInitialize(): void
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
    public function testValidationDefault(): void
    {
        $this->assertInstanceOf(Validator::class, $this->Duplicates->validationDefault(new Validator()));
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->assertInstanceOf(RulesChecker::class, $this->Duplicates->buildRules(new RulesChecker()));
    }

    public function testSave(): void
    {
        $data = [
            'model' => 'Articles',
            'original_id' => '00000000-0000-0000-0000-000000000001',
            'duplicate_id' => '00000000-0000-0000-0000-000000000001',
            'rule' => 'byTitleName',
            'status' => 'pending',
        ];

        $entity = $this->Duplicates->newEntity();
        $entity = $this->Duplicates->patchEntity($entity, $data);

        $this->assertInstanceOf(EntityInterface::class, $this->Duplicates->save($entity));
        $this->assertEmpty(array_diff($data, $entity->toArray()));
    }

    public function testMapDuplicates(): void
    {
        // overwrite Utils plugin configuration
        Configure::write('CsvMigrations.modules.path', Configure::read('Duplicates.path'));

        $this->assertSame([], $this->Duplicates->mapDuplicates());
    }

    public function testFetchByModelAndRule(): void
    {
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [
                ['id' => '00000000-0000-0000-0000-000000000002', 'value' => 'Second article', 'count' => 2],
            ],
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', []));
    }

    public function testFetchByModelAndRuleWithOptions(): void
    {
        $options = ['page' => 0, 'size' => 1];
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [
                ['id' => '00000000-0000-0000-0000-000000000002', 'value' => 'Second article', 'count' => 2],
            ],
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', $options));

        $options = ['page' => 1, 'size' => 1];
        $expected = [
            'pagination' => ['count' => 1],
            'data' => [], // page 1 has no data
        ];

        $this->assertSame($expected, $this->Duplicates->fetchByModelAndRule('Articles', 'byTitle', $options));
    }

    public function testFetchByOriginalIDAndRule(): void
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

        $this->assertSame(['id', 'author_id', 'title', 'excerpt', 'body', 'created', 'modified'], $result['fields']);

        $this->assertSame([], $result['virtualFields']);
    }

    public function testFetchByOriginalIDAndRuleWithInvalidID(): void
    {
        $result = $this->Duplicates->fetchByOriginalIDAndRule('00000000-0000-0000-0000-000000000404', 'byTitle');

        $this->assertSame([], $result);
    }

    public function testFalsePositiveByRuleAndIDs(): void
    {
        $ids = ['00000000-0000-0000-0000-000000000003'];

        $this->assertTrue($this->Duplicates->falsePositiveByRuleAndIDs('byTitle', $ids));

        $entity = $this->Duplicates->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame('processed', $entity->get('status'));
    }

    public function testFalsePositiveByRuleAndIDsWithInvalidID(): void
    {
        $ids = ['00000000-0000-0000-0000-000000000404'];
        $resultSet = $this->Duplicates->find()->all();

        $this->assertFalse($this->Duplicates->falsePositiveByRuleAndIDs('byTitle', $ids));
        $this->assertEquals($resultSet, $this->Duplicates->find()->all());
    }

    /**
     * Helper method for check deprecation methods
     *
     * @param callable $callable callable function that will receive asserts
     * @return void
     * @link https://github.com/cakephp/cakephp/blob/3.6.0/src/TestSuite/TestCase.php#L111-L126
     */
    public function deprecated($callable)
    {
        $errorLevel = error_reporting();
        error_reporting(E_ALL ^ E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            error_reporting($errorLevel);
        }
    }
}
