<?php
namespace Qobo\Duplicates;

use AdminLTE\View\Helper\submit;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;

/**
 * This class is responsible for persisting duplicated records to the database.
 */
final class Persister
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
     * @param \Cake\Datasource\RepositoryInterface $table Target table instance
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    public function __construct(RepositoryInterface $table, Rule $rule, ResultSetInterface $resultSet)
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
    public function execute()
    {
        $primaryKey = $this->table->getPrimaryKey();
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

        return $this->save($data);
    }

    /**
     * Validation errors getter.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Persists duplicate records into the database.
     *
     * @param array $data Records data
     * @return bool
     */
    private function save(array $data)
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $entities = $table->newEntities($data);

        if (! $table->saveMany($entities)) {
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
    public function getOriginal()
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
    public function isOriginal(EntityInterface $entity)
    {
        $primaryKey = $this->table->getPrimaryKey();

        return $this->getOriginal()->get($primaryKey) === $entity->get($primaryKey);
    }

    /**
     * Checks if duplicate record is already persisted.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity instance
     * @return bool
     */
    public function isPersisted(EntityInterface $entity)
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $primaryKey = $this->table->getPrimaryKey();

        $query = $table->find()
            ->select('duplicate_id')
            ->where(['duplicate_id' => $entity->get($primaryKey), 'rule' => $this->rule->getName()])
            ->limit(1);

        return ! $query->isEmpty();
    }
}
