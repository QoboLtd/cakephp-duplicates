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
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Qobo\Duplicates\Finder;
use Qobo\Duplicates\Persister;
use Qobo\Duplicates\Rule;
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

            $modulesData[] = [
                'table' => $table,
                'duplicates' => $this->findByModel($table, $config)
            ];
        }

        foreach ($modulesData as $moduleData) {
            foreach ($moduleData['duplicates'] as $ruleData) {
                foreach ($ruleData['entities'] as $entities) {
                    $this->saveEntities($ruleData['rule'], $moduleData['table'], $entities);
                }
            }
        }

        return $this->mapErrors;
    }

    /**
     * Find Model duplicates.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param array $config Duplicates configuration
     * @return array
     */
    private function findByModel(RepositoryInterface $table, array $config)
    {
        $result = [];
        foreach ($config as $ruleName => $ruleConfig) {
            $rule = new Rule($ruleName, $ruleConfig);

            $result[] = [
                'rule' => $rule,
                'entities' => $this->findByRule($rule, $ruleConfig, $table)
            ];
        }

        return $result;
    }

    /**
     * Find duplicates by rule.
     *
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @param array $ruleConfig Duplicates rule configuration
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return array
     */
    private function findByRule(Rule $rule, array $ruleConfig, RepositoryInterface $table)
    {
        $finder = new Finder($table, $rule);

        $result = [];
        foreach ($finder->execute() as $resultSet) {
            $result[] = $resultSet;
        }

        return $result;
    }

    /**
     * Persists duplicated records to the databse.
     *
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    private function saveEntities(Rule $rule, RepositoryInterface $table, ResultSetInterface $resultSet)
    {
        $persister = new Persister($table, $rule, $resultSet);
        $persister->execute();

        foreach ($persister->getErrors() as $error) {
            array_push($this->mapErrors, sprintf('Failed to save duplicate record: "%s"', $error));
        }
    }
}
