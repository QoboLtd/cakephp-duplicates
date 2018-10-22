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
     * Original entity.
     *
     * @var \Cake\Datasource\EntityInterface
     */
    private $original;

    /**
     * Duplicate entity.
     *
     * @var \Cake\Datasource\EntityInterface
     */
    private $duplicate;

    /**
     * Duplicate record entry in duplicates table.
     *
     * @var \Cake\Datasource\EntityInterface
     */
    private $entry;

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
     * Duplicates list.
     *
     * @var \Cake\Datasource\EntityInterface[]
     */
    private $duplicates = [];

    /**
     * Duplicates merging data.
     *
     * @var array
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
     */
    public function __construct(RepositoryInterface $table, EntityInterface $original)
    {
        $this->table = TableRegistry::get('Qobo/Duplicates.Duplicates');
        $this->target = $table;
        $this->original = $original;
    }

    /**
     * Duplicate entity setter.
     *
     * @param \Cake\Datasource\EntityInterface $duplicate Duplicate Entity
     * @return void
     */
    public function addDuplicate(EntityInterface $duplicate) : void
    {
        $originalId = $this->original->get($this->target->getPrimaryKey());
        $duplicateId = $duplicate->get($this->target->getPrimaryKey());

        $entry = $this->table->find('all')->where([
            'duplicate_id' => $duplicateId,
            'original_id' => $originalId
        ])->first();

        // relevant entry not found in duplicates table, duplicate will not be added to the list.
        if (! $entry instanceof EntityInterface) {
            return;
        }

        $this->duplicates[] = [
            'entry' => $entry,
            // re-fetch duplicate entity with associated data
            'entity' => $this->target->get($duplicateId, ['contain' => $this->target->associations()->keys()])
        ];
    }

    /**
     * Request data setter, to be used when merging duplicates with original entity.
     *
     * @param array $data Request data
     * @return void
     */
    public function addMergeData(array $data) : void
    {
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
            $this->entry = $duplicate['entry'];
            $this->duplicate = $duplicate['entity'];

            if (! $this->_process()) {
                $this->errors[] = sprintf(
                    'Failed to process %s duplicate with ID %s',
                    $this->target->getAlias(),
                    $this->duplicate->get($this->target->getPrimaryKey())
                );

                $result = false;
            }
        }

        return $result;
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
     * @return bool
     */
    private function _process() : bool
    {
        return $this->target->getConnection()->transactional(function () {
            if (! $this->deleteEntry()) {
                return false;
            }

            if (! $this->deleteDuplicate()) {
                return false;
            }

            if (! $this->inheritData()) {
                return false;
            }

            return true;
        });
    }

    /**
     * Delete duplicate record entry from duplicates table.
     *
     * @return bool
     */
    private function deleteEntry() : bool
    {
        return (bool)$this->table->delete($this->entry, ['atomic' => false]);
    }

    /**
     * Delete duplicate entity.
     *
     * @return bool
     */
    private function deleteDuplicate() : bool
    {
        return (bool)$this->target->delete($this->duplicate, ['atomic' => false]);
    }

    /**
     * Inherit duplicate entity associated data.
     *
     * @return bool
     */
    private function inheritData() : bool
    {
        foreach ($this->target->associations() as $association) {
            if (! $this->inheritDataByAssociation($association)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Inherit duplicate entity associated data per association.
     *
     * @param \Cake\ORM\Association $association Association instance
     * @return bool
     */
    private function inheritDataByAssociation(Association $association) : bool
    {
        if (! in_array($association->type(), self::ASSOCIATIONS)) {
            return true;
        }
        if (! $this->duplicate->has($association->getProperty())) {
            return true;
        }

        $data = $this->duplicate->get($association->getProperty());
        if (! $association->link($this->original, $data, ['atomic' => false])) {
            return false;
        }

        return true;
    }
}
