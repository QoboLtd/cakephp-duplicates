<?php
namespace Qobo\Duplicates\Filter;

final class StartsWithFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return sprintf('SUBSTRING(%s, -%d, %d)', $this->get('field'), $this->get('length'), $this->get('length'));
    }
}
