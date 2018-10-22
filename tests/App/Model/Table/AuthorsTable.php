<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;

class AuthorsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('authors');
        $this->primaryKey('id');

        $this->hasMany('Articles');
    }
}
