<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 */
class ArticlesFixture extends TestFixture
{

    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false],
        'title' => ['type' => 'string', 'null' => true],
        'excerpt' => ['type' => 'string', 'null' => true],
        'body' => 'text',
        'created' => ['type' => 'datetime', 'null' => false],
        'modified' => ['type' => 'datetime', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'title' => 'First Article',
            'body' => 'First Article Body',
            'excerpt' => 'First',
            'created' => '2018-08-10 17:33:54',
            'modified' => '2018-08-10 17:33:54'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Second Article',
            'body' => 'Second Article Body',
            'excerpt' => 'Second',
            'created' => '2018-08-10 17:33:55',
            'modified' => '2018-08-10 17:33:55'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'title' => 'Third Article',
            'body' => 'Third Article Body',
            'excerpt' => 'Third',
            'created' => '2018-08-10 17:33:56',
            'modified' => '2018-08-10 17:33:56'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'title' => 'Fourth Article',
            'body' => 'Fourth Article Body',
            'excerpt' => 'Fourth',
            'created' => '2018-08-10 17:33:57',
            'modified' => '2018-08-10 17:33:57'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000005',
            'title' => 'Fifth Article',
            'body' => 'Fifth Article Body',
            'excerpt' => 'Fifth',
            'created' => '2018-08-10 17:33:58',
            'modified' => '2018-08-10 17:33:58'
        ],
    ];
}
