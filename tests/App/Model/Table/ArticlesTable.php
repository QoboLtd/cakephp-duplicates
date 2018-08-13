<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('articles');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
    }
}
