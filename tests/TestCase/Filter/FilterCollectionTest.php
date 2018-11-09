<?php
namespace Qobo\Duplicates\Test\TestCase\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\EndsWithFilter;
use Qobo\Duplicates\Filter\ExactFilter;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Filter\FilterInterface;
use Qobo\Duplicates\Filter\StartsWithFilter;

class FilterCollectionTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $filters = [
            new ExactFilter(['field' => 'foo']),
            new StartsWithFilter(['field' => 'foo']),
            new EndsWithFilter(['field' => 'foo'])
        ];
        $this->instance = new FilterCollection(...$filters);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->instance);

        parent::tearDown();
    }

    public function testFilterInstance()
    {
        foreach ($this->instance as $filter) {
            $this->assertInstanceOf(FilterInterface::class, $filter);
        }
    }
}
