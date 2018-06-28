<?php
namespace Qobo\Duplicates\Filter;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * Properties list.
     *
     * @var array
     */
    private $properties = [];

    /**
     * Constructor method.
     *
     * @param array $properties Properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritDoc}
     */
    final public function get($property)
    {
        return array_key_exists($property, $this->properties) ? $this->properties[$property] : '';
    }
}
