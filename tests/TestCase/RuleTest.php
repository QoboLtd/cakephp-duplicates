<?php
namespace Qobo\Duplicates\Filter;

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
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $filters = [
            new StartsWithFilter(['field' => 'title', 'length' => 10]),
            new EndsWithFilter(['field' => 'excerpt', 'length' => 10])
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

    public function testGetName()
    {
        $this->assertEquals('foobar', $this->instance->getName());
    }

    public function testGetFilters()
    {
        $this->assertInstanceOf(FilterCollection::class, $this->instance->getFilters());
        foreach ($this->instance->getFilters() as $filter) {
            $this->assertInstanceOf(FilterInterface::class, $filter);
        }
    }

    public function testConstructWithInvalidNameType()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule(['foobar'], new FilterCollection(...[
            new StartsWithFilter(['field' => 'title', 'length' => 10])
        ]));
    }

    public function testConstructWithInvalidNameString()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule('  ', new FilterCollection(...[
            new StartsWithFilter(['field' => 'title', 'length' => 10])
        ]));
    }
}
