<?php
namespace Qobo\Duplicates\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesTagsFixture extends TestFixture
{
    public $table = 'articles_tags';

    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false],
        'article_id' => ['type' => 'uuid', 'null' => false],
        'tag_id' => ['type' => 'uuid', 'null' => false],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'article_id' => '00000000-0000-0000-0000-000000000003',
            'tag_id' => '00000000-0000-0000-0000-000000000001'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'article_id' => '00000000-0000-0000-0000-000000000003',
            'tag_id' => '00000000-0000-0000-0000-000000000002'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'article_id' => '00000000-0000-0000-0000-000000000002',
            'tag_id' => '00000000-0000-0000-0000-000000000003'
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'article_id' => '00000000-0000-0000-0000-000000000001',
            'tag_id' => '00000000-0000-0000-0000-000000000003'
        ]
    ];
}
