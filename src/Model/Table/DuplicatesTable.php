<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Qobo\Duplicates\Model\Table;

use Cake\Core\Configure;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Qobo\Duplicates\Event\EventName;
use Qobo\Duplicates\Filter\FilterCollection;
use Qobo\Duplicates\Finder;
use Qobo\Duplicates\Persister;
use Qobo\Duplicates\Rule;
use Qobo\Duplicates\RuleInterface;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Qobo\Utils\Utility;

/**
 * Duplicates Model
 */
class DuplicatesTable extends Table
{
    /**
     * Mapping validation errors list.
     *
     * @var array
     */
    private $mapErrors = [];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('qobo_duplicates');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->scalar('model')
            ->maxLength('model', 255)
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->uuid('original_id')
            ->requirePresence('original_id', 'create')
            ->notEmpty('original_id');

        $validator
            ->uuid('duplicate_id')
            ->requirePresence('duplicate_id', 'create')
            ->notEmpty('duplicate_id');

        $validator
            ->scalar('rule')
            ->maxLength('rule', 255)
            ->requirePresence('rule', 'create')
            ->notEmpty('rule');

        $validator
            ->scalar('status')
            ->maxLength('status', 255)
            ->requirePresence('status', 'create')
            ->notEmpty('status')
            ->add('status', 'custom', [
                'rule' => function ($value, array $context) {
                    return in_array($value, Configure::readOrFail('Duplicates.status.list'));
                },
                'message' => sprintf(
                    'Only following statuses are supported: "%s"',
                    implode(', ', Configure::readOrFail('Duplicates.status.list'))
                )
            ]);

        return $validator;
    }

    /**
     * Finds and persists duplicate records.
     *
     * Returns all validation errors.
     *
     * @return array
     */
    public function mapDuplicates()
    {
        $modulesData = [];
        foreach (Utility::findDirs(Configure::readOrFail('Duplicates.path')) as $model) {
            $config = json_decode(json_encode((new ModuleConfig(ConfigType::DUPLICATES(), $model))->parse()), true);
            if (empty($config)) {
                continue;
            }

            $table = TableRegistry::getTableLocator()->get($model);

            $this->mapByModel($table, $config);
        }

        return $this->mapErrors;
    }

    /**
     * Map Model duplicates.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param array $config Duplicates configuration
     * @return void
     */
    private function mapByModel(RepositoryInterface $table, array $config)
    {
        foreach ($config as $ruleName => $filtersConfig) {
            $filters = array_map(function ($conf) {
                return new $conf['filter']($conf);
            }, $filtersConfig);

            $this->mapByRule(new Rule($ruleName, new FilterCollection(...$filters)), $table);
        }
    }

    /**
     * Map duplicates by rule.
     *
     * @param \Qobo\Duplicates\RuleInterface $rule Rule instance
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return void
     */
    private function mapByRule(RuleInterface $rule, RepositoryInterface $table)
    {
        $finder = new Finder($table, $rule, 10);

        while ($resultSets = $finder->execute()) {
            foreach ($resultSets as $resultSet) {
                $this->saveEntities($rule, $table, $resultSet);
            }
        }
    }

    /**
     * Persists duplicated records to the databse.
     *
     * @param \Qobo\Duplicates\RuleInterface $rule Rule instance
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    private function saveEntities(RuleInterface $rule, RepositoryInterface $table, ResultSetInterface $resultSet)
    {
        $persister = new Persister($table, $rule, $resultSet);
        $persister->execute();

        foreach ($persister->getErrors() as $error) {
            array_push($this->mapErrors, sprintf('Failed to save duplicate record: "%s"', $error));
        }
    }

    /**
     * Fetches duplicates by model and rule name.
     *
     * @param string $model Model name
     * @param string $rule Rule name
     * @param array $options Query options
     * @return array
     */
    public function fetchByModelAndRule($model, $rule, array $options)
    {
        $table = TableRegistry::getTableLocator()->get($model);

        $query = $this->find();
        $query->select(['original_id', 'count' => 'COUNT(*)']);
        $query->group('original_id');
        $query->where(['status' => Configure::read('Duplicates.status.default'), 'model' => $model, 'rule' => $rule]);
        $query->order(['count' => 'DESC', 'original_id' => 'ASC']);

        $result = [
            'pagination' => ['count' => $query->count()],
            'data' => []
        ];

        if (isset($options['page']) && isset($options['size'])) {
            $query->offset((int)$options['page'] * (int)$options['size'])
                ->limit((int)$options['size']);
        }

        foreach ($query->all() as $entity) {
            array_push($result['data'], [
                'id' => $entity->get('original_id'),
                'value' => $table->get($entity->get('original_id'))->get($table->getDisplayField()),
                'count' => (int)$entity->get('count')
            ]);
        }

        return $result;
    }

    /**
     * Fetches duplicates by original id and rule name.
     *
     * @param string $id Original ID
     * @param string $rule Rule name
     * @return array
     */
    public function fetchByOriginalIDAndRule($id, $rule)
    {
        $resultSet = $this->find('all')
            ->select(['duplicate_id', 'model'])
            ->where(['original_id' => $id, 'rule' => $rule, 'status' => Configure::read('Duplicates.status.default')])
            ->all();

        if ($resultSet->isEmpty()) {
            return [];
        }

        $table = TableRegistry::getTableLocator()->get($resultSet->first()->get('model'));
        $original = $table->get($id);
        $ids = [];
        foreach ($resultSet as $entity) {
            $ids[] = $entity->get('duplicate_id');
        }

        $data = [
            'original' => $original,
            'duplicates' => $table->find()->where([$table->getPrimaryKey() . ' IN' => $ids])->all(),
            'fields' => $original->visibleProperties(),
            'virtualFields' => $original->virtualProperties()
        ];
        $event = new Event((string)EventName::DUPLICATE_AFTER_FIND(), $this, [
            'table' => $table,
            'data' => $data
        ]);
        $this->getEventManager()->dispatch($event);

        if (! empty($this->getEventManager()->listeners((string)EventName::DUPLICATE_AFTER_FIND()))) {
            $data = $event->getResult();
        }

        return $data;
    }

    /**
     * Deletes duplicates by IDs.
     *
     * @param string $model Model name
     * @param array $ids Duplicate IDs
     * @return bool
     */
    public function deleteDuplicates($model, array $ids)
    {
        $table = TableRegistry::getTableLocator()->get($model);
        foreach ($ids as $id) {
            $record = $table->find()
                ->where([$table->getPrimaryKey() => $id])
                ->first();
            if (null === $record) {
                return false;
            }

            $entity = $this->find()
                ->where(['OR' => ['duplicate_id' => $id, 'original_id' => $id]])
                ->first();
            if (null === $entity) {
                return false;
            }

            $this->getConnection()->transactional(function () use ($table, $record, $entity) {
                $table->delete($record, ['atomic' => false]);
                $this->delete($entity, ['atomic' => false]);
            });
        }

        return true;
    }

    /**
     * Flags duplicates as false positive by rule name and duplicate IDs.
     *
     * @param string $rule Rule name
     * @param array $ids Duplicate IDs
     * @return bool
     */
    public function falsePositiveByRuleAndIDs($rule, array $ids)
    {
        $resultSet = $this->find('all')
            ->where(['duplicate_id IN' => $ids, 'rule' => $rule])
            ->all();

        if ($resultSet->isEmpty()) {
            return false;
        }

        $duplicateIds = [];
        foreach ($resultSet as $entity) {
            $duplicateIds[] = $entity->get('id');
        }

        $this->updateAll(['status' => 'processed'], ['id IN' => $duplicateIds]);

        return true;
    }

    /**
     * Merges duplicates by updating original record with provided data.
     *
     * @param string $model Model name
     * @param string $id Original id
     * @param array $data Merge data
     * @return bool
     */
    public function mergeDuplicates($model, $id, array $data)
    {
        $table = TableRegistry::getTableLocator()->get($model);
        $entity = $table->get($id);
        $entity = $table->patchEntity($entity, $data);

        return (bool)$table->save($entity);
    }
}
