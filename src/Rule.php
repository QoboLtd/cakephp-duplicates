<?php
namespace Qobo\Duplicates;

use InvalidArgumentException;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Filter\FilterInterface;
use RuntimeException;

/**
 * This is a duplicates rule configuration class.
 */
final class Rule implements RuleInterface
{
    /**
     * Name.
     *
     * @var string
     */
    private $name = '';

    /**
     * Filters list.
     *
     * @var array
     */
    private $filters = [];

    /**
     * Constructor method.
     *
     * @param string $name Rule name
     * @param \Qobo\Duplicates\Filter\FilterCollection $filters Filters collection
     * @return void
     */
    public function __construct($name, FilterCollection $filters)
    {
        if (! is_string($name)) {
            throw new InvalidArgumentException('Rule name must be a string');
        }

        if ('' === trim($name)) {
            throw new InvalidArgumentException('Rule name is required');
        }

        $this->name = $name;
        $this->filters = $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Builds query filters.
     *
     * @return array
     */
    public function buildFilters()
    {
        $result = [];
        foreach ($this->getFilters() as $filter) {
            $result = array_merge($result, [$filter->getValue() => 'literal']);
        }

        return $result;
    }
}
