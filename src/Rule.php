<?php
namespace Qobo\Duplicates;

use InvalidArgumentException;
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
     * @param array $config Rule configuration
     * @return void
     */
    public function __construct($name, array $config)
    {
        $this->validateName($name);

        $this->name = $name;

        foreach ($config as $item) {
            $this->validateFilter($item);

            $className = 'Qobo\\Duplicates\\Filter\\' . ucfirst($item['filter']) . 'Filter';
            if (! class_exists($className)) {
                throw new RuntimeException(sprintf('Filter class "%s" does not exist', $className));
            }

            $filter = new $className($item);
            if (! $filter instanceof FilterInterface) {
                throw new RuntimeException(
                    sprintf('Class "%s" must implement "%s" interface', $className, FilterInterface::class)
                );
            }

            array_push($this->filters, $filter);
        }
    }

    /**
     * Rule name validator.
     *
     * @param string $name Validator name
     * @return void
     * @throws \InvalidArgumentException when name variable is not a string or is empty
     */
    private function validateName($name)
    {
        if (! is_string($name)) {
            throw new InvalidArgumentException('Rule name must be a string');
        }

        if ('' === trim($name)) {
            throw new InvalidArgumentException('Rule name is required');
        }
    }

    /**
     * Rule filter validator.
     *
     * @param array $config Filter configuration
     * @return void
     * @throws \InvalidArgumentException when filter key is not defined or filter value is not a string or is empty
     */
    private function validateFilter(array $config)
    {
        if (! isset($config['filter'])) {
            throw new InvalidArgumentException('Rule filter name is required');
        }

        if (! is_string($config['filter'])) {
            throw new InvalidArgumentException('Rule filter name must be a string');
        }

        if ('' === trim($config['filter'])) {
            throw new InvalidArgumentException('Rule filter name is required');
        }
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
}
