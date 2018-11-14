<?php
namespace Qobo\Duplicates;

use AdminLTE\View\Helper\submit;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use InvalidArgumentException;

/**
 * This class is responsible for persisting duplicated records to the database.
 */
final class Persister
{
    /**
     * Target ORM table instance.
     *
     * @var \Cake\ORM\Table
     */
    private $table;

    /**
     * Duplicates Rule instance.
     *
     * @var \Qobo\Duplicates\RuleInterface
     */
    private $rule;

    /**
     * Result set instance.
     *
     * @var \Cake\Datasource\ResultSetInterface
     */
    private $resultSet;

    /**
     * Validation errors list.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Constructor method.
     *
     * @param \Cake\ORM\Table $table Target table instance
     * @param \Qobo\Duplicates\RuleInterface $rule Rule instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    public function __construct(RepositoryInterface $table, RuleInterface $rule, ResultSetInterface $resultSet)
    {
        $this->table = $table;
        $this->rule = $rule;
        $this->resultSet = $resultSet;
    }

    /**
     * Executes duplicate records persistence logic.
     *
     * @return bool
     */
    public function execute(): bool
    {
        $primaryKey = $this->table->getPrimaryKey();
        if (! is_string($primaryKey)) {
            throw new InvalidArgumentException('Primary key must be a string');
        }

        $data = [];
        foreach ($this->resultSet as $entity) {
            if ($this->isOriginal($entity)) {
                continue;
            }

            if ($this->isPersisted($entity)) {
                continue;
            }

            array_push($data, [
                'model' => App::shortName(get_class($this->table), 'Model/Table', 'Table'),
                'original_id' => $this->getOriginal()->get($primaryKey),
                'duplicate_id' => $entity->get($primaryKey),
                'rule' => $this->rule->getName(),
                'status' => Configure::readOrFail('Duplicates.status.default'),
            ]);
        }

        if (empty($data)) {
            return true;
        }

        return $this->save($data) ? true : false;
    }

    /**
     * Validation errors getter.
     *
     * @return mixed[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Persists duplicate records into the database.
     *
     * @param mixed[] $data Records data
     * @return bool
     */
    private function save(array $data): bool
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        /**
         * This docblock was added to avoid the confusion created by
         * phpstan 0.9 and how union and intersection types are treated.
         *
         * @link https://medium.com/@ondrejmirtes/union-types-vs-intersection-types-fd44a8eacbb
         * @var \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet
         */
        $entities = $table->newEntities($data);

        if (! $table->saveMany($entities)) {
            /**
             * This docblock was added to avoid the confusion created by
             * phpstan 0.9 and how union and intersection types are treated.
             *
             * @link https://medium.com/@ondrejmirtes/union-types-vs-intersection-types-fd44a8eacbb
             * @var \Cake\Datasource\EntityInterface[]
             */
            $entities = $entities;
            array_walk($entities, function ($entity) {
                array_push($this->errors, json_encode($entity->getErrors()));
            });

            return false;
        }

        return true;
    }

    /**
     * Duplicates original record getter.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    public function getOriginal(): EntityInterface
    {
        $resultSet = clone $this->resultSet;

        return $resultSet->first();
    }

    /**
     * Validates if entity is the original record.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity instance
     * @return bool
     */
    public function isOriginal(EntityInterface $entity): bool
    {
        $primaryKey = $this->table->getPrimaryKey();
        if (! is_string($primaryKey)) {
            throw new InvalidArgumentException('Primary key must be a string');
        }

        return $this->getOriginal()->get($primaryKey) === $entity->get($primaryKey);
    }

    /**
     * Checks if duplicate record is already persisted.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity instance
     * @return bool
     */
    public function isPersisted(EntityInterface $entity): bool
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');

        $primaryKey = $this->table->getPrimaryKey();
        if (! is_string($primaryKey)) {
            throw new InvalidArgumentException('Primary key must be a string');
        }

        $query = $table->find()
            ->select('duplicate_id')
            ->where(['duplicate_id' => $entity->get($primaryKey), 'rule' => $this->rule->getName()])
            ->limit(1);

        return ! $query->isEmpty();
    }
}
