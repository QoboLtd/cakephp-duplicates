<?php
namespace Qobo\Duplicates\Filter;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Qobo\Duplicates\Filter\EndsWithFilter;
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

        $this->instance = new Rule('foobar', [
            ['field' => 'title', 'filter' => 'startsWith', 'length' => 10],
            ['field' => 'excerpt', 'filter' => 'endsWith', 'length' => 10]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown()
    {
        unset($this->instance);

        parent::tearDown();
    }

    public function testConstructWithInvalidNameType()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule(['foobar'], [
            ['field' => 'title', 'filter' => 'startsWith', 'length' => 10]
        ]);
    }

    public function testConstructWithInvalidNameString()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule('  ', [
            ['field' => 'title', 'filter' => 'startsWith', 'length' => 10]
        ]);
    }

    public function testConstructWithoutFilterName()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule('foobar', [
            ['field' => 'title', 'length' => 10]
        ]);
    }

    public function testConstructWithInvalidFilterNameType()
    {
        $this->expectException(InvalidArgumentException::class);

        new Rule('foobar', [
            ['field' => 'title', 'filter' => ['startsWith'], 'length' => 10]
        ]);
    }

    public function testConstructWithInvalidFilterName()
    {
        $this->expectException(RuntimeException::class);

        new Rule('foobar', [
            ['field' => 'title', 'filter' => 'invalidFilter', 'length' => 10]
        ]);
    }

    public function testGetName()
    {
        $this->assertEquals('foobar', $this->instance->getName());
    }

    public function testGetFilters()
    {
        $this->assertInternalType('array', $this->instance->getFilters());
        $this->assertInstanceOf(StartsWithFilter::class, $this->instance->getFilters()[0]);
        $this->assertInstanceOf(EndsWithFilter::class, $this->instance->getFilters()[1]);
    }
}
