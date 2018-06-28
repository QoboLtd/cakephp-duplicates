<?php
namespace Qobo\Duplicates\Filter;

/**
 * This is filter
 */
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
