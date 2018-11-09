<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
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
     * @param mixed[] $properties Properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Property getter.
     *
     * @param string $property Property name
     * @return string
     */
    final public function get($property): string
    {
        return array_key_exists($property, $this->properties) ? $this->properties[$property] : '';
    }
}
