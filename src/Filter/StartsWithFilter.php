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

final class StartsWithFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return sprintf('SUBSTRING(%s, 1, %d)', $this->get('field'), $this->get('length'));
    }
}
