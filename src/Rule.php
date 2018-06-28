<?php
namespace Qobo\Duplicates;

use InvalidArgumentException;

/**
 * This is a duplicates rule configuration class.
 */
final class Rule
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
            $className = 'Qobo\Duplicates\Filter\\' . ucfirst($item['filter']) . 'Filter';
            array_push($this->filters, new $className($item));
        }
    }

    /**
     * Rule name getter.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Rule filters getter.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
}