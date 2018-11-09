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

use ArrayIterator;
use IteratorAggregate;

/**
 * This collection class is responsible for storing \Qobo\Duplicates\Filter\FilterInterface objects.
 */
final class FilterCollection implements IteratorAggregate
{
    /**
     * Filter instances list.
     *
     * @var array
     */
    private $filters;

    /**
     * Constructor method.
     *
     * @param mixed[] $filters List of filter instances
     */
    public function __construct(FilterInterface ...$filters)
    {
        $this->filters = $filters;
    }
    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->filters);
    }
}
