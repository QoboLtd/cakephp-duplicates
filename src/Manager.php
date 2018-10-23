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
     * Target associations for inheriting associated data.
     */
    const ASSOCIATIONS = ['manyToMany', 'oneToMany'];

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
     * @var \Cake\Datasource\EntityInterface[]
     */
    private $duplicates = [];

    /**
     * Duplicates merging data.
     *
     * @var []
     */
    private $data = [];

    /**
     * Processing errors list.
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * Constructor method.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Target table instance
     * @param \Cake\Datasource\EntityInterface $original Original Entity
     * @param array $data Request data
     * @return void
     */
    public function __construct(RepositoryInterface $table, EntityInterface $original, array $data = [])
    {
        $this->table = TableRegistry::get('Qobo/Duplicates.Duplicates');
        $this->target = $table;
        $this->original = $original;
        $this->data = $data;
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
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate Entity
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

        // re-fetch duplicate entity with associated data
        $this->duplicates[] = $this->target->get($entry->get('duplicate_id'), [
            'contain' => $this->target->associations()->keys()
        ]);
    }

    /**
     * Process duplicates.
     *
     * @return bool
     */
    public function process() : bool
    {
        // avoid deleting duplicates if failed to merge with original.
        if (! $this->updateOriginal()) {
            $this->errors[] = 'Failed to merge duplicates';

            return false;
        }

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
     * Updates original Entity, used in duplicates merging cases.
     *
     * @return bool
     */
    private function updateOriginal() : bool
    {
        // no data to update
        if (empty($this->data)) {
            return true;
        }

        $id = $this->original->get($this->target->getPrimaryKey());

        // re-fetching original entity to avoid losing associated data.
        $original = $this->target->get($id);
        $original = $this->target->patchEntity($original, $this->data);

        return (bool)$this->target->save($original);
    }

    /**
     * Processes duplicate Entity in a single transaction.
     *
     * All of the following operations are executed:
     * - Delete entry from duplicates table
     * - Delete duplicate entity from corresponding table
     * - Link duplicate entity associated data with original entity
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function _process(EntityInterface $duplicate) : bool
    {
        return $this->target->getConnection()->transactional(function () use ($duplicate) {
            if (! $this->deleteEntry($this->fetchEntry($duplicate))) {
                return false;
            }

            if (! $this->deleteDuplicate($duplicate)) {
                return false;
            }

            if (! $this->inheritData($duplicate)) {
                return false;
            }

            return true;
        });
    }

    /**
     * Delete duplicate record entry from duplicates table.
     *
     * @param \Cake\Datasource\EntityInterface $entry Duplicate entry
     * @return bool
     */
    private function deleteEntry(EntityInterface $entry) : bool
    {
        return (bool)$this->table->delete($entry, ['atomic' => false]);
    }

    /**
     * Delete duplicate entity.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function deleteDuplicate(EntityInterface $duplicate) : bool
    {
        return (bool)$this->target->delete($duplicate, ['atomic' => false]);
    }

    /**
     * Inherit duplicate entity associated data.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @return bool
     */
    private function inheritData(EntityInterface $duplicate) : bool
    {
        foreach ($this->target->associations() as $association) {
            if (! $this->inheritDataByAssociation($duplicate, $association)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inherit duplicate entity associated data per association.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate entity
     * @param \Cake\ORM\Association $association Association instance
     * @return bool
     */
    private function inheritDataByAssociation(EntityInterface $duplicate, Association $association) : bool
    {
        if (! in_array($association->type(), self::ASSOCIATIONS)) {
            return true;
        }
        if (! $duplicate->has($association->getProperty())) {
            return true;
        }

        $data = $duplicate->get($association->getProperty());
        if (! $association->link($this->original, $data, ['atomic' => false])) {
            return false;
        }

        return true;
    }
}
