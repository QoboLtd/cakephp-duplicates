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
namespace Qobo\Duplicates;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Association;
use Cake\ORM\TableRegistry;

/**
 * Duplicates Manager
 */
final class Manager
{
    /**
     * Duplicates table.
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    private $table;

    /**
     * Target table.
     *
     * @var \Cake\Datasource\RepositoryInterface
     */
    private $target;

    /**
     * Original entity.
     *
     * @var \Cake\Datasource\EntityInterface
     */
    private $original;

    /**
     * Duplicates list.
     *
     * @var array
     */
    private $duplicates = [];

    /**
     * Duplicates merging data.
     *
     * @var array $data
     */
    private $data = [];

    /**
     * Processing errors list.
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * Inheritable associations list.
     * @var array
     */
    private $associations = [];

    /**
     * Flag for duplicates merging status.
     *
     * @var bool
     */
    private $merged = false;

    /**
     * Constructor method.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Target table instance
     * @param \Cake\Datasource\EntityInterface $original Original entity
     * @param mixed[] $data Request data
     * @return void
     */
    public function __construct(RepositoryInterface $table, EntityInterface $original, array $data = [])
    {
        $this->table = TableRegistry::get('Qobo/Duplicates.Duplicates');
        $this->target = $table;
        $this->original = $original;

        // load inheritable associated data
        $this->target->loadInto($this->original, array_keys($this->getAssociations()));
        $this->data = $data;
    }

    /**
     * Inheritable associations getter.
     *
     * Filters out many-to-one associations and one-to-many associations
     * with the junction table which are automatically set by CakePHP's ORM.
     *
     * @return \Cake\ORM\Association[]
     */
    private function getAssociations() : array
    {
        if (! empty($this->associations)) {
            return $this->associations;
        }

        $junctions = [];
        foreach ($this->target->associations() as $association) {
            if (Association::MANY_TO_MANY === $association->type()) {
                $this->associations[$association->getName()] = $association;
                $junctions[] = $association->junction()->getTable();
            }
        }

        foreach ($this->target->associations() as $association) {
            if (Association::ONE_TO_MANY !== $association->type()) {
                continue;
            }

            if (in_array($association->getTarget()->getTable(), $junctions)) {
                continue;
            }

            $this->associations[$association->getName()] = $association;
        }

        return $this->associations;
    }

    /**
     * Duplicates processing errors.
     *
     * @return string[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Duplicates resultset setter.
     *
     * @param \Cake\Datasource\ResultSetInterface $resultSet Duplicates result set
     * @return void
     */
    public function addDuplicates(ResultSetInterface $resultSet) : void
    {
        foreach ($resultSet as $duplicate) {
            $this->addDuplicate($duplicate);
        }
    }

    /**
     * Duplicate entity setter.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return void
     */
    public function addDuplicate(EntityInterface $duplicate) : void
    {
        $entry = $this->fetchEntry($duplicate);

        if (null === $entry) {
            $this->errors[] = sprintf(
                'Relevant entry not found, duplicate with ID "%s" will not be processed.',
                $duplicate->get($this->target->getPrimaryKey())
            );

            return;
        }

        $this->duplicates[] = $duplicate;
    }

    /**
     * Process duplicates.
     *
     * @return bool
     */
    public function process() : bool
    {
        $result = true;
        foreach ($this->duplicates as $duplicate) {
            if (! $this->_process($duplicate)) {
                $this->errors[] = sprintf(
                    'Failed to process %s duplicate with ID %s',
                    $this->target->getAlias(),
                    $duplicate->get($this->target->getPrimaryKey())
                );

                $result = false;
            }
        }

        return $result;
    }

    /**
     * Fetches duplicate table entry for specified entity.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function fetchEntry(EntityInterface $duplicate) : ?EntityInterface
    {
        return $this->table->find('all')
            ->where([
                'duplicate_id' => $duplicate->get($this->target->getPrimaryKey()),
                'original_id' => $this->original->get($this->target->getPrimaryKey())
            ])
            ->first();
    }

    /**
     * Processes duplicate entity in a single transaction.
     *
     * All of the following operations are executed:
     * - Delete entry from duplicates table
     * - Delete duplicate entity from corresponding table
     * - Link duplicate entity associated data with original entity
     * - Merge duplicate entity with original
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function _process(EntityInterface $duplicate) : bool
    {
        return $this->target->getConnection()->transactional(function () use ($duplicate) {
            if (! $this->merge()) {
                return false;
            }

            if (! $this->inherit($duplicate)) {
                return false;
            }

            if (! $this->delete($duplicate)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Updates original Entity, used in duplicates merging cases.
     *
     * @return bool
     */
    private function merge() : bool
    {
        // already merged
        if ($this->merged) {
            return true;
        }

        // no data to update
        if (empty($this->data)) {
            return true;
        }

        // flag as merged
        $this->merged = true;

        $this->original = $this->target->patchEntity($this->original, $this->data);

        return (bool)$this->target->save($this->original);
    }

    /**
     * Deletes duplicate entity and relevant entry record in duplicates table.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function delete(EntityInterface $duplicate) : bool
    {
        if (! $this->table->delete($this->fetchEntry($duplicate), ['atomic' => false])) {
            return false;
        }

        if (! $this->target->delete($duplicate, ['atomic' => false])) {
            return false;
        }

        return true;
    }

    /**
     * Inherit duplicate entity associated data.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function inherit(EntityInterface $duplicate) : bool
    {
        foreach ($this->getInheritData($duplicate) as $associationName => $data) {
            if (! $this->target->{$associationName}->link($this->original, $data, ['atomic' => false])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieves duplicate entity associated data that will be inherited by the original entity.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return mixed[]
     */
    private function getInheritData(EntityInterface $duplicate) : array
    {
        // load duplicate associated data
        $this->target->loadInto($duplicate, array_keys($this->getAssociations()));

        $result = [];
        foreach ($this->getAssociations() as $association) {
            $data = $this->getInheritDataByAssociation($duplicate, $association);
            if (empty($data)) {
                continue;
            }

            $result[$association->getName()] = $data;
        }

        return $result;
    }

    /**
     * Inherit duplicate entity associated data per association.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @param \Cake\ORM\Association $association Association instance
     * @return \Cake\Datasource\EntityInterface[]
     */
    private function getInheritDataByAssociation(EntityInterface $duplicate, Association $association) : array
    {
        if (empty($duplicate->get($association->getProperty()))) {
            return [];
        }

        // inherit all association data from duplicate entity to the original
        if (empty($this->original->get($association->getProperty()))) {
            return $duplicate->get($association->getProperty());
        }

        // inherit only association data not already associated with original, prevents duplication :)
        return $this->filterInheritData(
            $duplicate->get($association->getProperty()),
            $this->original->get($association->getProperty()),
            $association->getTarget()->getPrimaryKey()
        );
    }

    /**
     * Filters out duplicate entity's associated data that are already associated with the original entity.
     *
     * @param \Cake\Datasource\EntityInterface[] $duplicateData Duplicate associated data
     * @param \Cake\Datasource\EntityInterface[] $originalData Original associated data
     * @param string $primaryKey Primary key of associated data
     * @return \Cake\Datasource\EntityInterface[]
     */
    private function filterInheritData(array $duplicateData, array $originalData, string $primaryKey) : array
    {
        $existing = array_map(function ($entity) use ($primaryKey) {
            return $entity->get($primaryKey);
        }, $originalData);

        return array_filter($duplicateData, function ($entity) use ($primaryKey, $existing) {
            return ! in_array($entity->get($primaryKey), $existing);
        });
    }
}
