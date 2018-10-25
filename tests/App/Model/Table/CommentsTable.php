<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;

class CommentsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('comments');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Articles');
    }
}
