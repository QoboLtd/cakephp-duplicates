<?php
namespace Qobo\Duplicates;

use InvalidArgumentException;
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
        if (! is_string($name)) {
            throw new InvalidArgumentException('Rule name must be a string');
        }

        if ('' === trim($name)) {
            throw new InvalidArgumentException('Rule name is required');
        }

        $this->name = $name;

        foreach ($config as $item) {
            if (! isset($item['filter'])) {
                throw new InvalidArgumentException('Rule filter name is required');
            }

            if (! is_string($item['filter'])) {
                throw new InvalidArgumentException('Rule filter name must be a string');
            }

            $className = 'Qobo\Duplicates\Filter\\' . ucfirst($item['filter']) . 'Filter';
            if (! class_exists($className)) {
                throw new RuntimeException(sprintf('Filter class does not exist: %s', $className));
            }

            array_push($this->filters, new $className($item));
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
