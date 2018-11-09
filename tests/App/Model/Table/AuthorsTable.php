<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;

class AuthorsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('authors');
        $this->setPrimaryKey('id');

        $this->hasMany('Articles');
    }
}
