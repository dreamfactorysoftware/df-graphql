<?php

namespace DreamFactory\Core\GraphQL\Type;

use DreamFactory\Core\Database\Schema\RelationSchema;
use DreamFactory\Core\Enums\DbSimpleTypes;
use DreamFactory\Core\Models\BaseModel;
use DreamFactory\Core\GraphQL\Type\Type as GraphQLType;
use GraphQL\Type\Definition\Type;
use GraphQL;
use ServiceManager;

class BaseType extends GraphQLType
{
    protected $setRequired = false;

    public function __construct($attributes = [])
    {
        $this->inputObject = array_get_bool($attributes, 'for_input');
        $this->setRequired = array_get_bool($attributes, 'set_required');

        parent::__construct(array_except($attributes, ['set_required', 'for_input']));
    }

    /**
     * @param      $type
     * @param null $sub
     * @return GraphQL\Type\Definition\BooleanType|GraphQL\Type\Definition\FloatType|GraphQL\Type\Definition\IntType|GraphQL\Type\Definition\ListOfType|GraphQL\Type\Definition\StringType
     * @throws \DreamFactory\Core\GraphQL\Exception\TypeNotFound
     */
    public static function convertType($type, $sub = null)
    {
        switch ($type) {
            case DbSimpleTypes::TYPE_ARRAY:
                if ($sub && ($subType = static::convertType($sub))) {
                    return Type::listOf($subType);
                } else {
                    return null;
                }
                break;
            // not sure what to do with these yet
            case DbSimpleTypes::TYPE_COLUMN:
            case DbSimpleTypes::TYPE_REF_CURSOR:
            case DbSimpleTypes::TYPE_ROW:
            case DbSimpleTypes::TYPE_TABLE:
                return null;
            default:
                switch (DbSimpleTypes::toPhpType($type)) {
                    case 'array':
                        if ($sub && ($subType = static::convertType($sub))) {
                            return Type::listOf($subType);
                        } else {
                            return null;
                        }
                        break;
                    case 'boolean':
                        return Type::boolean();
                    case 'integer':
                        return Type::int();
                    case 'double':
                        return Type::float();
                    case 'string':
                        return Type::string();
                    default:
                        return GraphQL::type($type);
                }
                break;
        }
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public function fields()
    {
        if ($fields = array_get($this->attributes, 'fields')) {
            return $fields;
        }

        if ($fields = array_get($this->attributes, 'properties')) {
            foreach ($fields as $name => &$field) {
                if (isset($field['items']['properties'])) {
                    unset($fields[$name]);
                    continue 1; // todo breaking!
                }
                if ($type = static::convertType(array_get($field, 'type'), array_get($field, 'items.type'))) {
                    $field['type'] = $type;
                    if ($this->setRequired && array_get_bool($field, 'required')) {
                        $field['type'] = Type::nonNull($type);
                    }
                }
            }

            return $fields;
        }

        $out = [];
        $schema = null;
        if ($modelClass = array_get($this->attributes, 'model')) {
            /** @var BaseModel $model */
            $model = new $modelClass;
            $schema = $model->getTableSchema();
        } else {
            $schema = array_get($this->attributes, 'schema');
        }

        if ($schema) {
            foreach ($schema->getColumns(true) as $name => $column) {
                if ($type = static::convertType($column->type)) {
                    if ($this->setRequired && $column->getRequired()) {
                        $type = Type::nonNull($type);
                    }
                    $out[$name] = ['name' => $name, 'type' => $type, 'description' => $column->description];
                }
            }
            foreach ($schema->getRelations(true) as $name => $relation) {
                $refTable = $relation->refTable;
                $refService = ServiceManager::getServiceNameById($relation->refServiceId);
                $refTable = $refService . '_table_' . $refTable;
                if ($this->inputObject) {
                    $refTable .= '_input';
                }
                switch ($relation->type) {
                    case RelationSchema::BELONGS_TO:
                        $type = GraphQL::type($refTable);
                        break;
                    case RelationSchema::HAS_ONE:
                        $type = GraphQL::type($refTable);
                        break;
                    case RelationSchema::HAS_MANY:
                        $type = Type::listOf(GraphQL::type($refTable));
                        break;
                    case RelationSchema::MANY_MANY:
                        $type = Type::listOf(GraphQL::type($refTable));
                        break;
                    default:
                        throw new \Exception('Invalid relation type.');
                        break;
                }
                $out[$name] = ['name' => $name, 'type' => $type, 'description' => $relation->description];
            }
        }

        return $out;
    }
}