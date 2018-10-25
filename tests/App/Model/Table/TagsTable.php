<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;

class TagsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('tags');
        $this->primaryKey('id');

        $this->belongsToMany('Articles');
    }
}
