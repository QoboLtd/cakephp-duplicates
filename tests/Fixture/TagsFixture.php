<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TagsFixture extends TestFixture
{
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false],
        'name' => ['type' => 'string', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => '#first-tag'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'name' => '#second-tag'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'name' => '#third-tag'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'name' => '#fourth-tag'
        ]
    ];
}
