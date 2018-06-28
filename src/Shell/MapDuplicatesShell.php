<?php
namespace Qobo\Duplicates\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Qobo\Duplicates\Finder;
use Qobo\Duplicates\Persister;
use Qobo\Duplicates\Rule;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Qobo\Utils\Utility;

/**
 * Map Duplicates shell command.
 */
class MapDuplicatesShell extends Shell
{
    /**
     * {@inheritDoc}
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        $parser->description('Map Duplicate records');

        return $parser;
    }

    /**
     * Finds and persists duplicate records.
     *
     * @return void
     */
    public function main()
    {
        $this->out($this->OptionParser->help());

        foreach (Utility::findDirs(Configure::readOrFail('Duplicates.path')) as $model) {
            $config = json_decode(json_encode((new ModuleConfig(ConfigType::DUPLICATES(), $model))->parse()), true);
            if (empty($config)) {
                continue;
            }

            $this->findByModel($model, $config);
        }

        $this->success('Duplicates mapped successfully');
    }

    /**
     * Find Model duplicates.
     *
     * @param string $model Model name
     * @param array $config Duplicates configuration
     * @return void
     */
    private function findByModel($model, array $config)
    {
        foreach ($config as $ruleName => $ruleConfig) {
            $this->findByRule($ruleName, $ruleConfig, TableRegistry::getTableLocator()->get($model));
        }
    }

    /**
     * Find duplicates by rule.
     *
     * @param string $ruleName Rule name
     * @param array $ruleConfig Duplicates rule configuration
     * @param ]Cake\Datasource\RepositoryInterface $table Table instance
     * @return void
     */
    private function findByRule($ruleName, array $ruleConfig, RepositoryInterface $table)
    {
        $rule = new Rule($ruleName, $ruleConfig);
        $finder = new Finder($table, $rule);

        foreach ($finder->execute() as $resultSet) {
            $this->save($rule, $table, $resultSet);
        }
    }

    /**
     * Persists duplicated records to the databse.
     *
     * @param \Qobo\Duplicates\Rule $rule Rule instance
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param \Cake\Datasource\ResultSetInterface $resultSet Result set
     * @return void
     */
    private function save(Rule $rule, RepositoryInterface $table, ResultSetInterface $resultSet)
    {
        $persister = new Persister($table, $rule, $resultSet);
        if ($persister->execute()) {
            return;
        }

        foreach ($persister->getErrors() as $error) {
            $this->err(sprintf('Failed to save duplicate record: "%s"', $error));
        }

        $this->abort('Aborting, failed to persist duplicate records.');
    }
}
