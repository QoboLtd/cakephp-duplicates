<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class DuplicatesFixture extends TestFixture
{
    public $table = 'qobo_duplicates';

    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'model' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'original_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'duplicate_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'rule' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'status' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'latin1_swedish_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'latin1_swedish_ci'
        ],
    ];

    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Articles',
            'original_id' => '00000000-0000-0000-0000-000000000002',
            'duplicate_id' => '00000000-0000-0000-0000-000000000003',
            'rule' => 'byTitle',
            'status' => 'pending',
            'created' => '2018-08-07 17:45:16',
            'modified' => '2018-08-07 17:45:16'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'model' => 'Articles',
            'original_id' => '00000000-0000-0000-0000-000000000002',
            'duplicate_id' => '00000000-0000-0000-0000-000000000004',
            'rule' => 'byTitle',
            'status' => 'pending',
            'created' => '2018-10-22 16:56:06',
            'modified' => '2018-10-22 16:56:06'
        ],
    ];
}
