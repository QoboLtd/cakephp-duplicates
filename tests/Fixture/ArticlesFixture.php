<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false],
        'author_id' => ['type' => 'uuid', 'null' => false],
        'title' => ['type' => 'string', 'null' => true],
        'excerpt' => ['type' => 'string', 'null' => true],
        'body' => 'text',
        'created' => ['type' => 'datetime', 'null' => false],
        'modified' => ['type' => 'datetime', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'author_id' => '00000000-0000-0000-0000-000000000001',
            'title' => 'First article',
            'body' => 'First article body',
            'excerpt' => 'First',
            'created' => '2018-08-10 17:33:54',
            'modified' => '2018-08-10 17:33:54'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'author_id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Second article',
            'body' => 'Second article body',
            'excerpt' => 'Second',
            'created' => '2018-08-10 17:33:55',
            'modified' => '2018-08-10 17:33:55'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'author_id' => '00000000-0000-0000-0000-000000000001',
            'title' => 'Third article',
            'body' => 'Third article body',
            'excerpt' => 'Third',
            'created' => '2018-08-10 17:33:56',
            'modified' => '2018-08-10 17:33:56'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'author_id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Fourth article',
            'body' => 'Fourth article body',
            'excerpt' => 'Fourth',
            'created' => '2018-08-10 17:33:57',
            'modified' => '2018-08-10 17:33:57'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000005',
            'author_id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Fifth article',
            'body' => 'Fifth article body',
            'excerpt' => 'Fifth',
            'created' => '2018-08-10 17:33:58',
            'modified' => '2018-08-10 17:33:58'
        ],
    ];
}
