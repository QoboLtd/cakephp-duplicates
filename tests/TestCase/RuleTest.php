<?php
namespace Qobo\Duplicates\Test\TestCase;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Qobo\Duplicates\Filter\EndsWithFilter;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Filter\FilterInterface;
use Qobo\Duplicates\Filter\StartsWithFilter;
use Qobo\Duplicates\Rule;
use RuntimeException;

class RuleTest extends TestCase
{
    /**
     * @var \Qobo\Duplicates\RuleInterface $instance
     */
    protected $instance;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $filters = [
            new StartsWithFilter(['field' => 'title', 'length' => 10]),
            new EndsWithFilter(['field' => 'excerpt', 'length' => 10]),
        ];

        $this->instance = new Rule('foobar', new FilterCollection(...$filters));
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->instance);

        parent::tearDown();
    }

    public function testGetName(): void
    {
        $this->assertSame('foobar', $this->instance->getName());
    }

    public function testGetFilters(): void
    {
        $this->assertInstanceOf(FilterCollection::class, $this->instance->getFilters());
        foreach ($this->instance->getFilters() as $filter) {
            $this->assertInstanceOf(FilterInterface::class, $filter);
        }
    }

    public function testBuildFilters(): void
    {
        $expected = [
            'SUBSTR(title, 1, 10)' => 'literal',
            'SUBSTR(excerpt, -10, 10)' => 'literal',
        ];

        $this->assertSame($expected, $this->instance->buildFilters());
    }

    public function testConstructWithInvalidNameString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule('  ', new FilterCollection(...[
            new StartsWithFilter(['field' => 'title', 'length' => 10]),
        ]));
    }
}
