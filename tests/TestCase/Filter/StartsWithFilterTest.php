<?php
namespace Qobo\Duplicates\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\StartsWithFilter;

class StartsWithFilterTest extends TestCase
{
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

    public function testGetValue()
    {
        $this->assertSame('SUBSTR(foo, 1, 10)', $this->instance->getValue());
    }
}
