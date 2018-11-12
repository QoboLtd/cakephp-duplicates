<?php
namespace Qobo\Duplicates;

use Qobo\Duplicates\Filter\FilterCollection;

interface RuleInterface
{
    /**
     * Rule name getter.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Rule filters getter.
     *
     * @return \Qobo\Duplicates\Filter\FilterCollection
     */
    public function getFilters(): FilterCollection;
}
