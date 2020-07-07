<?php
namespace Qobo\Duplicates\Test\TestCase;

use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\EndsWithFilter;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Finder;
use Qobo\Duplicates\Rule;

class FinderTest extends TestCase
{
    public $fixtures = ['plugin.Qobo/Duplicates.Articles'];

    /**
     * @var array $filters
     */
    protected $filters;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->filters = [
            new EndsWithFilter(['field' => 'title', 'length' => 3]),
            new EndsWithFilter(['field' => 'excerpt', 'length' => 1]),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->filters);

        parent::tearDown();
    }

    public function testOffset(): void
    {
        $finder = new Finder(
            TableRegistry::getTableLocator()->get('Qobo/Duplicates.Articles'),
            new Rule('foobar', new FilterCollection(...$this->filters))
        );

        $this->assertSame(0, $finder->getOffset());

        $finder->setOffset(2);
        $this->assertSame(2, $finder->getOffset());

        $finder->resetOffset();
        $this->assertSame(0, $finder->getOffset());
    }

    public function testExecute(): void
    {
        $finder = new Finder(
            TableRegistry::getTableLocator()->get('Qobo/Duplicates.Articles'),
            new Rule('foobar', new FilterCollection(...$this->filters))
        );

        $result = $finder->execute();
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);

        foreach ($result as $resultSet) {
            $this->assertInstanceOf(ResultSetInterface::class, $resultSet);

            $this->assertSame(
                substr($resultSet->first()->get('title'), -3, 3),
                substr($resultSet->skip(1)->first()->get('title'), -3, 3)
            );

            $this->assertSame(
                substr($resultSet->first()->get('excerpt'), -1, 1),
                substr($resultSet->skip(1)->first()->get('excerpt'), -1, 1)
            );
        }
    }

    public function testExecuteWithLimit(): void
    {
        $finder = new Finder(
            TableRegistry::getTableLocator()->get('Qobo/Duplicates.Articles'),
            new Rule('foobar', new FilterCollection(...$this->filters)),
            1 // limit
        );

        $result = $finder->execute();
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);

        foreach ($result as $resultSet) {
            $this->assertInstanceOf(ResultSetInterface::class, $resultSet);

            $this->assertSame(
                substr($resultSet->first()->get('title'), -3, 3),
                substr($resultSet->skip(1)->first()->get('title'), -3, 3)
            );

            $this->assertSame(
                substr($resultSet->first()->get('excerpt'), -1, 1),
                substr($resultSet->skip(1)->first()->get('excerpt'), -1, 1)
            );
        }
    }
}
