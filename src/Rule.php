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
     * @var \Qobo\Duplicates\Filter\FilterCollection
     */
    private $filters;

    /**
     * Constructor method.
     *
     * @param string $name Rule name
     * @param \Qobo\Duplicates\Filter\FilterCollection $filters Filters collection
     * @return void
     */
    public function __construct(string $name, FilterCollection $filters)
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Rule name must be a non-empty string');
        }

        $this->name = $name;
        $this->filters = $filters;
    }

    /**
     * Rule name getter.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Rule filters getter.
     *
     * @return \Qobo\Duplicates\Filter\FilterCollection
     */
    public function getFilters(): \Qobo\Duplicates\Filter\FilterCollection
    {
        return $this->filters;
    }

    /**
     * Builds query filters.
     *
     * @return mixed[]
     */
    public function buildFilters(): array
    {
        $result = [];
        foreach ($this->getFilters() as $filter) {
            $result = array_merge($result, [$filter->getValue() => 'literal']);
        }

        return $result;
    }
}
