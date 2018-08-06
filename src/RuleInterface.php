<?php
namespace Qobo\Duplicates;

interface RuleInterface
{
    /**
     * Rule name getter.
     *
     * @return string
     */
    public function getName();

    /**
     * Rule filters getter.
     *
     * @return array
     */
    public function getFilters();
}
