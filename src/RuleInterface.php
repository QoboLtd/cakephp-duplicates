<?php
namespace Qobo\Duplicates;

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
    public function getFilters(): \Qobo\Duplicates\Filter\FilterCollection;
}
