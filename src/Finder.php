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
     * Query limit.
     *
     * @var int
     */
    private $limit = 0;

    /**
     * Query offset.
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Constructor method.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Target table instance
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @param int $limit Query limit
     * @return void
     */
    public function __construct(RepositoryInterface $table, Rule $rule, $limit = 0)
    {
        $this->table = $table;
        $this->rule = $rule;
        $this->limit = (int)$limit;

        $this->resetOffset();
    }

    /**
     * Executes duplicate records retrieval logic.
     *
     * @return array
     */
    public function execute()
    {
        $query = $this->buildQuery();

        $result = [];
        foreach ($query->all() as $entity) {
            $result[] = $this->fetchByIDs(
                explode(',', $entity->get($this->table->getPrimaryKey()))
            );
        }

        return $result;
    }

    /**
     * Query offset getter.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Query offset setter.
     *
     * @param int $offset Query offset
     * @return void
     */
    public function setOffset($offset)
    {
        $this->offset = (int)$offset;
    }

    /**
     * Resets Query offset.
     *
     * @return void
     */
    public function resetOffset()
    {
        $this->offset = 0;
    }

    /**
     * Builds duplicates find query.
     *
     * @return \Cake\Datasource\QueryInterface
     */
    private function buildQuery()
    {
        $query = $this->table->find('all');

        $query->select([
                $this->table->getPrimaryKey() => sprintf('GROUP_CONCAT(%s)', $this->table->getPrimaryKey()),
                'checksum' => $query->func()->concat($this->buildFilters())
            ])
            ->group('checksum')
            ->having(['COUNT(*) > ' => 1, 'checksum !=' => '']);

        if (0 < $this->limit) {
            $query->limit($this->limit)
                ->offset($this->getOffset() * $this->limit);
        }

        $this->setOffset($this->getOffset() + 1);

        return $query;
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
     * Fetches duplicated records by IDs.
     *
     * @param array $ids Duplicates IDs
     * @return \Cake\Datasource\ResultSetInterface
     */
    private function fetchByIDs(array $ids)
    {
        $query = $this->table->find('all')
            ->where([$this->table->getPrimaryKey() . ' IN' => $ids]);

        if ($this->table->getSchema()->hasColumn('created')) {
            $query->order([$this->table->aliasField('created') => 'ASC']);
        }

        return $query->all();
    }
}
