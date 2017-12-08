<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Models\BaseModel;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class BaseModelQuery extends BaseQuery
{
    public function args()
    {
        $id = array_get($this->attributes, 'identifier', ['name' => 'id', 'type' => Type::nonNull(Type::int())]);
        $name = array_get($id, 'name', 'id');

        return [
            $name => $id,
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        $id = array_get($this->attributes, 'identifier', ['name' => 'id', 'type' => Type::nonNull(Type::int())]);
        $name = array_get($id, 'name', 'id');

        /** @var BaseModel $modelClass */
        if ($modelClass = array_get($this->attributes, 'model')) {
            return $modelClass::selectById($args[$name]);
        }

        return parent::resolve($root, $args, $context, $info);
    }
}