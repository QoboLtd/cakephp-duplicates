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
     * @var \Qobo\Duplicates\RuleInterface
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
     * @param \Qobo\Duplicates\RuleInterface $rule Rule instance
     * @param int $limit Query limit
     * @return void
     */
    public function __construct(RepositoryInterface $table, RuleInterface $rule, int $limit = 0)
    {
        $this->table = $table;
        $this->rule = $rule;
        $this->limit = $limit;

        $this->resetOffset();
    }

    /**
     * Executes duplicate records retrieval logic.
     *
     * @return mixed[]
     */
    public function execute(): array
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
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Query offset setter.
     *
     * @param int $offset Query offset
     * @return void
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * Resets Query offset.
     *
     * @return void
     */
    public function resetOffset(): void
    {
        $this->offset = 0;
    }

    /**
     * Builds duplicates find query.
     *
     * @return \Cake\Datasource\QueryInterface
     */
    private function buildQuery(): \Cake\Datasource\QueryInterface
    {
        $query = $this->table->find('all');

        $query->select([
                $this->table->getPrimaryKey() => sprintf('GROUP_CONCAT(%s)', $this->table->getPrimaryKey()),
                'checksum' => $query->func()->concat($this->rule->buildFilters())
            ])
            ->group('checksum')
            ->having(['COUNT(*) > ' => 1, 'checksum !=' => ''], ['COUNT(*)' => 'integer', 'checksum' => 'string']);

        if (0 < $this->limit) {
            $query->limit($this->limit)
                ->offset($this->getOffset() * $this->limit);
        }

        $this->setOffset($this->getOffset() + 1);

        return $query;
    }

    /**
     * Fetches duplicated records by IDs.
     *
     * @param mixed[] $ids Duplicates IDs
     * @return \Cake\Datasource\ResultSetInterface
     */
    private function fetchByIDs(array $ids): \Cake\Datasource\ResultSetInterface
    {
        $query = $this->table->find('all')
            ->where([$this->table->getPrimaryKey() . ' IN' => $ids]);

        if ($this->table->getSchema()->hasColumn('created')) {
            $query->order([$this->table->aliasField('created') => 'ASC']);
        }

        return $query->all();
    }
}
