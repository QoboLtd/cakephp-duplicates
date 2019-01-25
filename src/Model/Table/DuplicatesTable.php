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
use InvalidArgumentException;
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
     * @return mixed[]
     */
    public function mapDuplicates(): array
    {
        foreach (Utility::findDirs(Configure::readOrFail('Duplicates.path')) as $model) {
            $config = (new ModuleConfig(ConfigType::DUPLICATES(), $model))->parseToArray();
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
     * @param \Cake\ORM\Table $table Table instance
     * @param mixed[] $config Duplicates configuration
     * @return void
     */
    private function mapByModel(RepositoryInterface $table, array $config): void
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
     * @param \Cake\ORM\Table $table Table instance
     * @return void
     */
    private function mapByRule(RuleInterface $rule, RepositoryInterface $table): void
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
     * @param \Cake\ORM\Table $table Table instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    private function saveEntities(RuleInterface $rule, RepositoryInterface $table, ResultSetInterface $resultSet): void
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
     * @param mixed[] $options Query options
     * @return mixed[]
     */
    public function fetchByModelAndRule(string $model, string $rule, array $options): array
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

        $primaryKey = $table->getPrimaryKey();
        if (! is_string($primaryKey)) {
            throw new InvalidArgumentException('Primary key must be a string');
        }

        foreach ($query->all() as $entity) {
            /** @var \Cake\Datasource\EntityInterface|null */
            $original = $table->find()
                ->where([$primaryKey => $entity->get('original_id')])
                ->enableHydration(true)
                ->first();
            if (null === $original) {
                continue;
            }

            array_push($result['data'], [
                'id' => $entity->get('original_id'),
                'value' => $original->get($table->getDisplayField()),
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
     * @return mixed[]
     */
    public function fetchByOriginalIDAndRule(string $id, string $rule): array
    {
        $resultSet = $this->find('all')
            ->select(['duplicate_id', 'model'])
            ->where(['original_id' => $id, 'rule' => $rule, 'status' => Configure::read('Duplicates.status.default')])
            ->all();

        if ($resultSet->isEmpty()) {
            return [];
        }

        $table = TableRegistry::getTableLocator()->get($resultSet->first()->get('model'));

        $primaryKey = $table->getPrimaryKey();
        if (! is_string($primaryKey)) {
            throw new InvalidArgumentException('Primary key must be a string');
        }

        /** @var \Cake\Datasource\EntityInterface|null */
        $original = $table->find()
            ->where([$primaryKey => $id])
            ->enableHydration(true)
            ->first();
        if (null === $original) {
            return [];
        }

        $ids = [];
        foreach ($resultSet as $entity) {
            $ids[] = $entity->get('duplicate_id');
        }

        $data = [
            'original' => $original,
            'duplicates' => $table->find()->where([$primaryKey . ' IN' => $ids])->all(),
            'fields' => $original->visibleProperties(),
            'virtualFields' => $original->getVirtual()
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
     * Flags duplicates as false positive by rule name and duplicate IDs.
     *
     * @param string $rule Rule name
     * @param mixed[] $ids Duplicate IDs
     * @return bool
     */
    public function falsePositiveByRuleAndIDs(string $rule, array $ids): bool
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
}
