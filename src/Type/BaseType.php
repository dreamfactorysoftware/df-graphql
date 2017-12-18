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

    public static function convertType($type, $sub = null)
    {
        switch ($type) {
            case DbSimpleTypes::TYPE_BOOLEAN:
                $type = Type::boolean();
                break;
            case DbSimpleTypes::TYPE_ID:
            case DbSimpleTypes::TYPE_INTEGER:
            case DbSimpleTypes::TYPE_REF:
            case DbSimpleTypes::TYPE_USER_ID:
            case DbSimpleTypes::TYPE_USER_ID_ON_CREATE:
            case DbSimpleTypes::TYPE_USER_ID_ON_UPDATE:
                $type = Type::int();
                break;
            case DbSimpleTypes::TYPE_DECIMAL:
            case DbSimpleTypes::TYPE_DOUBLE:
            case DbSimpleTypes::TYPE_FLOAT:
                $type = Type::float();
                break;
            case DbSimpleTypes::TYPE_BIG_ID:
            case DbSimpleTypes::TYPE_BIG_INT:
            case DbSimpleTypes::TYPE_BINARY:
            case DbSimpleTypes::TYPE_DATE:
            case DbSimpleTypes::TYPE_DATETIME:
            case DbSimpleTypes::TYPE_MONEY:
            case DbSimpleTypes::TYPE_STRING:
            case DbSimpleTypes::TYPE_TEXT:
            case DbSimpleTypes::TYPE_TIME:
            case DbSimpleTypes::TYPE_TIMESTAMP:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_CREATE:
            case DbSimpleTypes::TYPE_TIMESTAMP_ON_UPDATE:
                $type = Type::string();
                break;
            case DbSimpleTypes::TYPE_ARRAY:
                $type = Type::listOf(static::convertType($sub));
                break;
            default:
                $type = GraphQL::type($type);
                break;
        }

        return $type;
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
                $field['type'] = static::convertType(array_get($field, 'type'), array_get($field, 'items.type'));
                if ($this->setRequired && array_get_bool($field, 'required')) {
                    $field['type'] = Type::nonNull($field['type']);
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
                $type = static::convertType($column->type);
                if ($this->setRequired && $column->getRequired()) {
                    $type = Type::nonNull($type);
                }
                $out[$name] = ['name' => $name, 'type' => $type, 'description' => $column->description];
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