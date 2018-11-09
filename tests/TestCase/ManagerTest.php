<?php
namespace Qobo\Duplicates\Test\TestCase;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\EventManager;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use PDOException;
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
     * @var \Qobo\Duplicates\Model\Table\DuplicatesTable $Duplicates
     */
    protected $Duplicates;

    /**
     * @var \Qobo\Duplicates\Test\App\Model\Table\ArticlesTable $table
     */
    protected $table;

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
        $table = TableRegistry::get('Qobo/Duplicates.Duplicates');
        $this->Duplicates = $table;

        /**
         * @var \Qobo\Duplicates\Test\App\Model\Table\ArticlesTable $table
         */
        $table = TableRegistry::get('Articles');
        $this->table = $table;
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

    public function testAddDuplicate(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));

        $this->assertTrue($manager->process());
    }

    public function testAddDuplicateWithInvalidEntry(): void
    {
        // invalid duplicate id, no such entry
        $id = '00000000-0000-0000-0000-000000000002';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));

        $this->assertTrue($manager->process());
    }

    public function testAddDuplicates(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $query = $this->table->find()->where(['id' => $id]);
        $manager->addDuplicates($query->all());

        $this->assertTrue($manager->process());
    }

    public function testAddDuplicatesWithInvalidEntry(): void
    {
        // invalid duplicate id, no such entry
        $id = '00000000-0000-0000-0000-000000000002';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $query = $this->table->find()->where(['id' => $id]);
        $manager->addDuplicates($query->all());

        $this->assertTrue($manager->process());
    }

    public function testGetErrrors(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'));
        $manager->addDuplicate($this->table->get($id));
        $manager->process();

        $this->assertEmpty($manager->getErrors());
    }

    public function testGetErrorsWithInvalidEntry(): void
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

    /**
     * @dataProvider invalidateProvider
     * @param mixed[] $data
     * @param string $callback
     */
    public function testGetErrorsWithInvalidData(array $data, string $callback = ''): void
    {
        // trigger callback
        if ('' !== trim($callback)) {
            call_user_func([$this, $callback]);
        }

        $id = '00000000-0000-0000-0000-000000000003';

        $manager = new Manager($this->table, $this->table->get('00000000-0000-0000-0000-000000000002'), $data);
        $manager->addDuplicate($this->table->get($id));
        $manager->process();

        $this->assertSame(
            [sprintf('Failed to process Articles duplicate with ID %s', $id)],
            $manager->getErrors()
        );
    }

    public function testProcessSuccessful(): void
    {
        $associations = $this->table->associations()->keys();

        $data = ['excerpt' => sprintf('Some really random excerpt %s', uniqid())];
        $originalId = '00000000-0000-0000-0000-000000000002';
        $ids = [
            '00000000-0000-0000-0000-000000000003',
            '00000000-0000-0000-0000-000000000004',
            '00000000-0000-0000-0000-000000000001' // invalid IDs are discarded
        ];
        $invalidDuplicate = $this->table->get($ids[2], ['contain' => $associations]);

        $manager = new Manager($this->table, $this->table->get($originalId), $data);
        foreach ($ids as $id) {
            $manager->addDuplicate($this->table->get($id));
        }

        $this->assertTrue($manager->process());

        // re-fetch original entity with all associated data
        $original = $this->table->get($originalId, ['contain' => $associations]);

        $this->assertSame(0, $this->Duplicates->find('all')->count());
        $this->assertSame($data['excerpt'], $original->get('excerpt'));

        $this->assertEquals(
            $invalidDuplicate,
            $this->table->get($invalidDuplicate->get('id'), ['contain' => $associations]),
            'Invalid duplicate Entity was modified'
        );

        $query = $this->table->find('all')->where(['id' => $ids[0]]);
        $this->assertTrue($query->isEmpty());

        $this->assertEquals('00000000-0000-0000-0000-000000000002', $original->get('author_id'), 'Original Entity initial associated data were modified');

        $comments = array_map(function ($comment) {
            return $comment->get('id');
        }, $original->get('comments'));
        sort($comments);
        $this->assertEquals([
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
            '00000000-0000-0000-0000-000000000003'
        ], $comments);

        $tags = array_map(function ($tag) {
            return $tag->get('id');
        }, $original->get('tags'));
        sort($tags);
        $this->assertEquals([
            '00000000-0000-0000-0000-000000000001',
            '00000000-0000-0000-0000-000000000002',
            '00000000-0000-0000-0000-000000000003',
            '00000000-0000-0000-0000-000000000004'
        ], $tags);

        $query = $this->Duplicates->find('all')->where(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($query->isEmpty());
    }

    /**
     *
     * @dataProvider invalidateProvider
     * @param mixed[] $data
     * @param string $callback
     */
    public function testProcessFailure(array $data, string $callback = ''): void
    {
        // trigger callback
        if ('' !== trim($callback)) {
            call_user_func([$this, $callback]);
        }

        $resultSet = $this->Duplicates->find('all')->all();
        $associations = $this->table->associations()->keys();

        $ids = [
            'original' => '00000000-0000-0000-0000-000000000002',
            'duplicate' => '00000000-0000-0000-0000-000000000003'
        ];
        $expected = [
            'original' => $this->table->get($ids['original'], ['contain' => $associations]),
            'duplicate' => $this->table->get($ids['duplicate'], ['contain' => $associations])
        ];

        $manager = new Manager($this->table, $this->table->get($ids['original']), $data);
        $manager->addDuplicate($this->table->get($ids['duplicate']));

        $this->assertFalse($manager->process(), 'Duplicates processing completed successfully');

        $this->assertEquals($resultSet, $this->Duplicates->find('all')->all(), 'Duplicate table entries were affected');

        $this->assertEquals($expected['original'], $this->table->get($ids['original'], ['contain' => $associations]), 'Original entity was modifed');
        $this->assertEquals($expected['duplicate'], $this->table->get($ids['duplicate'], ['contain' => $associations]), 'Duplicate entity was modifed');
    }

    /**
     * @return mixed[]
     */
    public function invalidateProvider(): array
    {
        return [
            [['title' => null]],
            [[], 'preventEntryDeletion'],
            [[], 'preventDuplicateDeletion'],
            [[], 'preventAssociatedLink']
        ];
    }

    private function preventEntryDeletion(): void
    {
        // prevent entry record deletion to fail the transactional operation
        EventManager::instance()->on('Model.beforeDelete', function ($event) {
            if ('Duplicates' === $event->getSubject()->getAlias()) {
                $event->stopPropagation();

                return false;
            }
        });
    }

    private function preventDuplicateDeletion(): void
    {
        // prevent duplicate entity deletion to fail the transactional operation
        EventManager::instance()->on('Model.beforeDelete', function ($event) {
            if ('Articles' === $event->getSubject()->getAlias()) {
                $event->stopPropagation();

                return false;
            }
        });
    }

    private function preventAssociatedLink(): void
    {
        // prevent associated record link to fail the transactional operation
        EventManager::instance()->on('Model.beforeSave', function ($event) {
            if ('Comments' === $event->getSubject()->getAlias()) {
                throw new PDOException();
            }
        });
    }
}
