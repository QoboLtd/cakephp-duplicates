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
namespace Qobo\Duplicates\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;
use Exception;
use Qobo\Duplicates\Manager;

/**
 * Duplicates Controller
 *
 * @property \Qobo\Duplicates\Model\Table\DuplicatesTable $Duplicates
 */
class DuplicatesController extends AppController
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');

        // allow only ajax requests
        $this->request->allowMethod(['ajax']);
    }

    /**
     * Items method.
     *
     * @param string $model Model name
     * @param string $rule Rule name
     * @return \Cake\Http\Response|void
     */
    public function items($model, $rule)
    {
        $this->request->allowMethod('get');

        $result = $this->Duplicates->fetchByModelAndRule($model, $rule, (array)$this->request->getQuery());

        $this->set('success', true);
        $this->set('data', $result['data']);
        $this->set('pagination', $result['pagination']);
        $this->set('_serialize', ['success', 'data', 'pagination']);
    }

    /**
     * View method.
     *
     * @param string $id Original ID
     * @param string $rule Rule name
     * @return \Cake\Http\Response|void
     */
    public function view($id, $rule)
    {
        $this->request->allowMethod('get');

        $data = $this->Duplicates->fetchByOriginalIDAndRule($id, $rule);

        $this->set('success', ! empty($data));
        ! empty($data) ?
            $this->set('data', $data) :
            $this->set('error', sprintf('Failed to fetch duplicates for record with ID "%s"', $id));
        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * Delete method.
     *
     * @param string $model Model name
     * @param string $id Original ID
     * @return \Cake\Http\Response|void
     */
    public function delete($model, $id)
    {
        $this->request->allowMethod('delete');

        $table = TableRegistry::get($model);

        try {
            $manager = new Manager($table, $table->get($id));

            $query = $table->find('all')
                ->where([$table->aliasField($table->getPrimaryKey()) . ' IN' => (array)$this->request->getData('ids')]);

            foreach ($query->all() as $duplicate) {
                $manager->addDuplicate($duplicate);
            }

            $success = $manager->process();

            $this->set('success', $success);
            $success ? $this->set('data', []) : $this->set('error', sprintf(
                'Failed to delete duplicates: %s',
                implode(', ', $manager->getErrors())
            ));
        } catch (Exception $e) {
            $this->set('success', false);
            $this->set('error', sprintf('Failed to delete duplicates: %s', $e->getMessage()));
        }

        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * False positive method.
     *
     * @param string $rule Rule name
     * @return \Cake\Http\Response|void
     */
    public function falsePositive($rule)
    {
        $this->request->allowMethod('post');

        $success = $this->Duplicates->falsePositiveByRuleAndIDs($rule, (array)$this->request->getData('ids'));

        $this->set('success', $success);
        $success ? $this->set('data', []) : $this->set('error', 'Failed to mark duplicates as false positive');
        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * Merge method.
     *
     * @param string $model Model name
     * @param string $id Original ID
     * @return \Cake\Http\Response|void
     */
    public function merge($model, $id)
    {
        $this->request->allowMethod('post');

        $table = TableRegistry::get($model);

        try {
            $manager = new Manager($table, $table->get($id), (array)$this->request->getData('data'));

            $query = $table->find('all')
                ->where([$table->aliasField($table->getPrimaryKey()) . ' IN' => (array)$this->request->getData('ids')]);

            foreach ($query->all() as $duplicate) {
                $manager->addDuplicate($duplicate);
            }

            $success = $manager->process();

            $this->set('success', $success);
            $success ? $this->set('data', []) : $this->set('error', sprintf(
                'Failed to merge duplicates: %s',
                implode(', ', $manager->getErrors())
            ));
        } catch (Exception $e) {
            $this->set('success', false);
            $this->set('error', sprintf('Failed to merge duplicates: %s', $e->getMessage()));
        }

        $this->set('_serialize', ['success', 'data', 'error']);
    }
}
