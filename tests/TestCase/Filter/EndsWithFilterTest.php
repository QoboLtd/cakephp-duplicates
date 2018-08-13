<?php
namespace Qobo\Duplicates\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\EndsWithFilter;

class EndsWithFilterTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->instance = new EndsWithFilter(['field' => 'foo', 'length' => 10]);
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
        $this->assertSame('SUBSTR(foo, -10, 10)', $this->instance->getValue());
    }
}
