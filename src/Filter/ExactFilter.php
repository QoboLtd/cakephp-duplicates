<?php
namespace Qobo\Duplicates\Filter;

final class ExactFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function getValue()
    {
        return $this->get('field');
    }
}
