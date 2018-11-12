<?php
namespace Qobo\Duplicates\Test\TestCase\Filter;

use Cake\TestSuite\TestCase;
use Qobo\Duplicates\Filter\ExactFilter;

class ExactFilterTest extends TestCase
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

    public function testGetValue() : void
    {
        $this->assertSame('foo', $this->instance->getValue());
    }
}
