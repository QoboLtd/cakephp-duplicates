<?php
namespace Qobo\Duplicates\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\ExactFilter;

class ExactFilterTest extends TestCase
{
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->instance = new ExactFilter(['field' => 'foo']);
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
        $this->assertSame('foo', $this->instance->getValue());
    }
}
