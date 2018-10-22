<?php
namespace Qobo\Duplicates\Test\TestCase;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Qobo\Duplicates\Manager;

/**
 * Qobo\Duplicates\Manager Test Case
 */
class ManagerTest extends TestCase
{
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.Qobo/Duplicates.articles',
        'plugin.Qobo/Duplicates.articles_tags',
        'plugin.Qobo/Duplicates.authors',
        'plugin.Qobo/Duplicates.comments',
        'plugin.Qobo/Duplicates.duplicates',
        'plugin.Qobo/Duplicates.tags'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Duplicates = TableRegistry::get('Qobo/Duplicates.Duplicates');
        $this->table = TableRegistry::get('Articles');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->table);
        unset($this->Duplicates);

        parent::tearDown();
    }

    public function testAddDuplicate()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));

        $this->assertTrue($manager->process());
    }

    public function testAddDuplicateWithInvalidEntry()
    {
        // invalid duplicate id, no such entry
        $id = '00000000-0000-0000-0000-000000000002';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));

        $this->assertTrue($manager->process());
    }

    public function testGetErrrors()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));
        $manager->process();

        $this->assertEmpty($manager->getErrors());
    }

    public function testGetErrorsWithInvalidEntry()
    {
        // invalid duplicate id, no such entry
        $id = '00000000-0000-0000-0000-000000000002';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));
        $manager->process();

        $this->assertSame(
            [sprintf('Relevant entry not found, duplicate with ID "%s" will not be processed.', $id)],
            $manager->getErrors()
        );
    }

    public function testProcess()
    {
        // get duplcicates count
        $count = $this->Duplicates->find('all')->count();

        $this->table = TableRegistry::get('Articles');
        $associations = $this->table->associations()->keys();

        $data = ['excerpt' => sprintf('Some really random excerpt %s', uniqid())];
        $originalId = '00000000-0000-0000-0000-000000000002';
        $ids = [
            '00000000-0000-0000-0000-000000000003',
            '00000000-0000-0000-0000-000000000001' // invalid IDs are discarded
        ];
        $invalidDuplicate = $this->table->get($ids[1], ['contain' => $associations]);

        $manager = new Manager($this->table, $this->table->get($originalId), $data);

        foreach ($ids as $id) {
            $manager->addDuplicate($this->table->get($id));
        }

        $this->assertTrue($manager->process());
        $this->assertSame(
            [sprintf('Relevant entry not found, duplicate with ID "%s" will not be processed.', $ids[1])],
            $manager->getErrors()
        );
        $this->assertSame($data['excerpt'], $this->table->get($originalId)->get('excerpt'));

        // assert invalid duplicate was not affected
        $this->assertEquals($invalidDuplicate, $this->table->get($ids[1], ['contain' => $associations]));
        $this->assertSame($count - 1, $this->Duplicates->find('all')->count());

        $query = $this->table->find('all')
            ->where(['id' => $ids[0]]);
        $this->assertTrue($query->isEmpty());

        // re-fetch original entity with all associated data
        $original = $this->table->get($originalId, ['contain' => $associations]);

        $this->assertEquals('00000000-0000-0000-0000-000000000002', $original->get('author_id'));

        $expected = [
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
            '00000000-0000-0000-0000-000000000003'
        ];
        $comments = array_map(function ($comment) {
            return $comment->get('id');
        }, $original->get('comments'));
        sort($comments);

        $this->assertEquals($expected, $comments);

        $tags = array_map(function ($tag) {
            return $tag->get('id');
        }, $original->get('tags'));
        sort($tags);

        $this->assertEquals($expected, $tags);

        $query = $this->Duplicates->find('all')
            ->where(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($query->isEmpty());
    }
}
