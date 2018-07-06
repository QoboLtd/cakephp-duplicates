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
     * Index method.
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $this->set('success', true);
        $this->set('data', $this->Duplicates->fetchByModelAndRule(
            $this->request->getParam('pass.0'),
            $this->request->getParam('pass.1')
        ));
        $this->set('_serialize', ['success', 'data']);
    }

    /**
     * View method.
     *
     * @param string $originalId Original ID
     * @param string $rule Rule name
     * @return \Cake\Http\Response|void
     */
    public function view($originalId, $rule)
    {
        $this->request->allowMethod(['get']);

        $this->set('success', true);
        $this->set('data', $this->Duplicates->fetchByOriginalIDAndRule($originalId, $rule));
        $this->set('_serialize', ['success', 'data']);
    }

    /**
     * Delete method.
     *
     * @param string $model Model name
     * @return \Cake\Http\Response|void
     */
    public function delete($model)
    {
        $this->request->allowMethod('delete');

        $success = $this->Duplicates->deleteDuplicates($model, (array)$this->request->getData('ids'));

        $this->set('success', $success);
        $success ? $this->set('data', []) : $this->set('error', 'Failed to delete duplicates');
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

        $success = $this->Duplicates->mergeDuplicates($model, $id, $this->request->getData('data'));
        $success = $this->Duplicates->deleteDuplicates($model, (array)$this->request->getData('ids'));

        $this->set('success', $success);
        $success ? $this->set('data', []) : $this->set('error', 'Failed to merge duplicates');
        $this->set('_serialize', ['success', 'data', 'error']);
    }
}
