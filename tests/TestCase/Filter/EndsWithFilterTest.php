<?php
namespace Qobo\Duplicates\Test\TestCase\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\EndsWithFilter;

class EndsWithFilterTest extends TestCase
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

    public function testGetValue(): void
    {
        $this->assertSame('SUBSTR(foo, -10, 10)', $this->instance->getValue());
    }
}
