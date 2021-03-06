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
namespace Qobo\Duplicates\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use NinjaMutex\MutexException;
use Qobo\Utils\Utility\Lock\FileLock;

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

        $parser->setDescription('Map Duplicate records');

        return $parser;
    }

    /**
     * Finds and persists duplicate records.
     *
     * @return bool|int|null
     */
    public function main()
    {
        try {
            $lock = new FileLock('import_' . md5(__FILE__));
        } catch (MutexException $e) {
            $this->warn($e->getMessage());

            return false;
        }

        if (! $lock->lock()) {
            $this->warn('Map duplicates is already in progress');

            return false;
        }

        /**
         * @var \Qobo\Duplicates\Model\Table\DuplicatesTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $result = $table->mapDuplicates();

        foreach ($result as $error) {
            $this->err($error);
        }

        empty($result) ?
            $this->success('Duplicates mapped successfully') :
            $this->abort('Aborting, failed to persist duplicate records.');

        $lock->unlock();

        return true;
    }
}
