<?php
namespace Qobo\Duplicates;

use Cake\Datasource\RepositoryInterface;

/**
 * This class is responsible for fetching duplicated records from the database.
 */
final class Finder
{
    /**
     * Target ORM table instance.
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    private $table;

    /**
     * Duplicates Rule instance.
     *
     * @var \Qobo\Duplicates\Rule
     */
    private $rule;

    /**
     * Query instance where the result is generated.
     *
     * @var \Cake\ORM\Query
     */
    private $query;

    /**
     * Constructor method.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Target table instance
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @return void
     */
    public function __construct(RepositoryInterface $table, Rule $rule)
    {
        $this->table = $table;
        $this->rule = $rule;
        $this->query = $this->table->find('all')
            ->select([$this->table->getPrimaryKey() => sprintf('GROUP_CONCAT(%s)', $this->table->getPrimaryKey())]);
    }

    /**
     * Executes duplicate records retrieval logic.
     *
     * @return array
     */
    public function execute()
    {
        $this->buildQuery();

        return $this->fetchAll();
    }

    /**
     * Builds duplicates find query.
     *
     * @return void
     */
    private function buildQuery()
    {
        $this->query->select(['checksum' => $this->query->func()->concat($this->buildFilters())])
            ->group('checksum')
            ->having(['COUNT(*) > ' => 1]);
    }

    /**
     * Builds query filters for sql CONCAT function.
     *
     * @return array
     */
    private function buildFilters()
    {
        $result = [];
        foreach ($this->rule->getFilters() as $filter) {
            $result = array_merge($result, [$filter->getValue() => 'literal']);
        }

        return $result;
    }

    /**
     * Fetches all duplicated records.
     *
     * @return array
     */
    private function fetchAll()
    {
        $result = [];
        foreach ($this->query->all() as $entity) {
            $result[] = $this->fetchByIDs(
                explode(',', $entity->get($this->table->getPrimaryKey()))
            );
        }

        return $result;
    }

    /**
     * Fetches duplicated records by IDs.
     *
     * @param array $ids Duplicates IDs
     * @return array
     */
    private function fetchByIDs(array $ids)
    {
        return $this->table->find('all')
            ->where([$this->table->getPrimaryKey() . ' IN' => $ids])
            ->order([$this->table->aliasField('created') => 'ASC'])
            ->all();
    }
}
