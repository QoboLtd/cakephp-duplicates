<?php
namespace Qobo\Duplicates\Test\TestCase\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\StartsWithFilter;

class StartsWithFilterTest extends TestCase
{
    /**
     * @var \Qobo\Duplicates\Filter\FilterInterface
     */
    private $instance;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->instance = new StartsWithFilter(['field' => 'foo', 'length' => 10]);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->instance);

        parent::tearDown();
    }

    public function testGetValue() : void
    {
        $this->assertSame('SUBSTR(foo, 1, 10)', $this->instance->getValue());
    }
}
