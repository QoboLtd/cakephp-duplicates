<?php
namespace Qobo\Duplicates\Filter;

/**
 * This is filter
 */
interface FilterInterface
{
    /**
     * Filter value getter.
     *
     * @return string
     */
    public function getValue();

    /**
     * Property getter.
     *
     * @param string $property Property name
     * @return string
     */
    public function get($property);
}
