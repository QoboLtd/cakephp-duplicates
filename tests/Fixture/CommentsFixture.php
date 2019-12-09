<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class CommentsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false],
        'article_id' => ['type' => 'uuid', 'null' => false],
        'content' => ['type' => 'string', 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'article_id' => '00000000-0000-0000-0000-000000000003',
            'content' => 'First comment',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'article_id' => '00000000-0000-0000-0000-000000000003',
            'content' => 'Second comment',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'article_id' => '00000000-0000-0000-0000-000000000002',
            'content' => 'Third comment',
        ],
    ];
}
