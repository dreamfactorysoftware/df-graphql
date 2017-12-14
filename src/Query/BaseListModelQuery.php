<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Models\BaseModel;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class BaseListModelQuery extends BaseQuery
{
    public function args()
    {
        $id = array_get($this->attributes, 'identifier', ['name' => 'id', 'type' => Type::int()]);
        $name = array_get($id, 'name', 'id');

        return [
            $name => $id,
        ];
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        /** @var BaseModel $modelClass */
        if ($modelClass = array_get($this->attributes, 'model')) {
            if (!empty($args)) {
                return $modelClass::selectByRequest($args);
            } else {
                return $modelClass::all();
            }
        }

        return parent::resolve($root, $args, $context, $info);
    }
}