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
    public function getValue(): string;

    /**
     * Property getter.
     *
     * @param string $property Property name
     * @return string
     */
    public function get(string $property): string;
}
