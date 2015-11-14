<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Model;
use Infuse\Model\ACLModel;
use Infuse\Model\Query;

class TestModel extends Model
{
    protected static $properties = [
        'relation' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'TestModel2',
            'null' => true,
        ],
        'answer' => [
            'type' => Model::TYPE_STRING,
        ],
        'mutator' => [],
        'accessor' => [],
    ];
    public $preDelete;
    public $postDelete;

    protected static $hidden = ['mutator', 'accessor'];
    protected static $appended = ['appended'];

    public static $query;

    protected function initialize()
    {
        self::$properties['test_hook'] = [
            'type' => Model::TYPE_STRING,
            'null' => true,
        ];

        parent::initialize();
    }

    public function preCreateHook()
    {
        $this->preCreate = true;

        return true;
    }

    public function postCreateHook()
    {
        $this->postCreate = true;
    }

    public function preSetHook()
    {
        $this->preSet = true;

        return true;
    }

    public function postSetHook()
    {
        $this->postSet = true;
    }

    public function preDeleteHook()
    {
        $this->preDelete = true;

        return true;
    }

    public function postDeleteHook()
    {
        $this->postDelete = true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (!isset($exclude['toArray'])) {
            $result['toArray'] = true;
        }
    }

    public static function query()
    {
        if ($query = self::$query) {
            self::$query = false;

            return $query;
        }

        return parent::query();
    }

    public static function setQuery(Query $query)
    {
        self::$query = $query;
    }

    protected function setMutatorValue($value)
    {
        return strtoupper($value);
    }

    protected function getAccessorValue($value)
    {
        return strtolower($value);
    }

    protected function getAppendedValue()
    {
        return true;
    }
}

function validate()
{
    return false;
}

class TestModel2 extends Model
{
    protected static $ids = ['id', 'id2'];

    protected static $properties = [
        'id' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'id2' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'default' => [
            'default' => 'some default value',
        ],
        'validate' => [
            'validate' => 'email',
            'null' => true,
        ],
        'validate2' => [
            'validate' => 'validate',
            'null' => true,
        ],
        'unique' => [
            'unique' => true,
        ],
        'required' => [
            'type' => Model::TYPE_NUMBER,
            'required' => true,
        ],
        'hidden' => [
            'type' => Model::TYPE_BOOLEAN,
            'default' => false,
        ],
        'person' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'Person',
            'default' => 20,
        ],
        'array' => [
            'type' => Model::TYPE_ARRAY,
            'default' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
        ],
        'object' => [
            'type' => Model::TYPE_OBJECT,
        ],
        'mutable_create_only' => [
            'mutable' => Model::MUTABLE_CREATE_ONLY,
        ],
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['validate2', 'hidden', 'person', 'array', 'object', 'mutable_create_only'];

    public static $query;

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (isset($include['toArrayHook'])) {
            $result['toArrayHook'] = true;
        }
    }

    public static function query()
    {
        if ($query = self::$query) {
            self::$query = false;

            return $query;
        }

        return parent::query();
    }

    public static function setQuery(Query $query)
    {
        self::$query = $query;
    }
}

class TestModelNoPermission extends ACLModel
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class TestModelHookFail extends Model
{
    public function preCreateHook()
    {
        return false;
    }

    public function preSetHook()
    {
        return false;
    }

    public function preDeleteHook()
    {
        return false;
    }
}

class Person extends ACLModel
{
    protected static $properties = [
        'id' => [
            'type' => Model::TYPE_STRING,
        ],
        'name' => [
            'type' => Model::TYPE_STRING,
            'default' => 'Jared',
        ],
        'email' => [
            'type' => Model::TYPE_STRING,
        ],
    ];

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class IteratorTestModel extends Model
{
    protected static $properties = [
        'name' => [],
    ];
}

class AclObject extends ACLModel
{
    public $first = true;

    protected function hasPermission($permission, Model $requester)
    {
        if ($permission == 'whatever') {
            // always say no the first time
            if ($this->first) {
                $this->first = false;

                return false;
            }

            return true;
        } elseif ($permission == 'do nothing') {
            return $requester->id() == 5;
        }
    }
}
