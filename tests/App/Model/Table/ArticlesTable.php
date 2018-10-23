<?php
namespace Qobo\Duplicates\Test\App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('articles');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Comments');
        $this->belongsTo('Authors');
        $this->belongsToMany('Tags');
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmpty('title');

        return $validator;
    }
}
