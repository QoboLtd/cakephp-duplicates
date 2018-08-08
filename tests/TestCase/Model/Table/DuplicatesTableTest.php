<?php
namespace Qobo\Duplicates\Test\TestCase\Model\Table;

use Cake\Datasource\EntityInterface;
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

        $this->assertEquals('qobo_duplicates', $this->Duplicates->getTable());
        $this->assertEquals('id', $this->Duplicates->getPrimaryKey());
        $this->assertEquals('id', $this->Duplicates->getDisplayField());

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
}
