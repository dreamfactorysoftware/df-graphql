<?php

namespace DreamFactory\Core\GraphQL\Components;

use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\GraphQL\Error\ValidationError;
use DreamFactory\Core\GraphQL\Events\SchemaAdded;
use DreamFactory\Core\GraphQL\Events\TypeAdded;
use DreamFactory\Core\GraphQL\Exception\TypeNotFound;
use DreamFactory\Core\GraphQL\Query\BaseListQuery;
use DreamFactory\Core\GraphQL\Type\BaseType;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Utility\ServiceRequest;
use DreamFactory\Core\Utility\Session;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Schema;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Log;
use ServiceManager;

class GraphQL
{
    protected $app;
    protected $schemas = [];
    protected $types = [];
    protected $typesInstances = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function schema($schema = null)
    {
        if ($schema instanceof Schema) {
            return $schema;
        }

        $this->clearTypeInstances();

        if (!is_array($schema)) {
            $schemaName = (empty($schema) ? config('graphql.schema', 'default') : $schema);
            if (!isset($this->schemas[$schemaName]) || empty($schema = $this->schemas[$schemaName])) {
                $schema = $this->buildDefaultSchema();
                foreach (ServiceManager::getServiceNames(true) as $serviceName) {
                    $service = ServiceManager::getService(strtolower($serviceName));
                    if (method_exists($service, 'getGraphQLSchema')) {
                        try {
                            $content = $service->getGraphQLSchema();
                            if (isset($content['query'])) {
                                $schema['query'] = array_merge((array)array_get($schema, 'query'),
                                    (array)$content['query']);
                            }
                            if (isset($content['mutation'])) {
                                $schema['mutation'] = array_merge((array)array_get($schema, 'mutation'),
                                    (array)$content['mutation']);
                            }
                            if (isset($content['types'])) {
                                $schema['types'] = array_merge((array)array_get($schema, 'types'),
                                    (array)$content['types']);
                            }
                        } catch (\Exception $e) {
                            \Log::debug('Service ' . $serviceName . ' failed to build GraphQL schema. ' . $e->getMessage());
//                            throw new InternalServerErrorException('Service ' . $serviceName . ' failed to build GraphQL schema. ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        $schemaQuery = array_get($schema, 'query', []);
        $schemaMutation = array_get($schema, 'mutation', []);
        $schemaSubscription = array_get($schema, 'subscription', []);
        $schemaTypes = array_get($schema, 'types', []);

        //Get the types either from the schema, or the global types.
        $types = [];
        if (sizeof($schemaTypes)) {
            foreach ($schemaTypes as $name => $type) {
                $this->types[$name] = $type;
                $objectType = $this->objectType($type, is_numeric($name) ? [] : [
                    'name' => $name
                ]);
                $this->typesInstances[$name] = $objectType;
                $types[] = $objectType;
            }
        } else {
            foreach ($this->types as $name => $type) {
                $types[] = $this->type($name);
            }
        }

        $query = $this->objectType($schemaQuery, [
            'name' => 'Query'
        ]);

        $mutation = $this->objectType($schemaMutation, [
            'name' => 'Mutation'
        ]);

        $subscription = $this->objectType($schemaSubscription, [
            'name' => 'Subscription'
        ]);

        return new Schema([
            'query'        => $query,
            'mutation'     => !empty($schemaMutation) ? $mutation : null,
            'subscription' => !empty($schemaSubscription) ? $subscription : null,
            'types'        => $types
        ]);
    }

    public function type($name, $fresh = false)
    {
        if (!isset($this->types[$name])) {
            throw new TypeNotFound('Type ' . $name . ' not found.');
        }

        if (!$fresh && isset($this->typesInstances[$name])) {
            return $this->typesInstances[$name];
        }

        $class = $this->types[$name];
        $type = $this->objectType($class, [
            'name' => $name
        ]);
        $this->typesInstances[$name] = $type;

        return $type;
    }

    public function objectType($type, $opts = [])
    {
        // If it's already an ObjectType, just update properties and return it.
        // If it's an array, assume it's an array of fields and build ObjectType
        // from it. Otherwise, build it from a string or an instance.
        $objectType = null;
        if ($type instanceof ObjectType) {
            $objectType = $type;
            foreach ($opts as $key => $value) {
                if (property_exists($objectType, $key)) {
                    $objectType->{$key} = $value;
                }
                if (isset($objectType->config[$key])) {
                    $objectType->config[$key] = $value;
                }
            }
        } elseif (is_array($type)) {
            $objectType = $this->buildObjectTypeFromFields($type, $opts);
        } else {
            $objectType = $this->buildObjectTypeFromClass($type, $opts);
        }

        return $objectType;
    }

    public function query($query, $variables = [], $opts = [])
    {
        $root = array_get($opts, 'root', null);
        $context = array_get($opts, 'context', null);
        $schemaName = array_get($opts, 'schema', null);
        $operationName = array_get($opts, 'operationName', null);

        $schema = $this->schema($schemaName);

        $result = GraphQLBase::executeAndReturnResult($schema, $query, $root, $context, $variables, $operationName);

        if (!empty($result->errors)) {
            $errorFormatter = config('graphql.error_formatter', [self::class, 'formatError']);

            return [
                'data'   => $result->data,
                'errors' => array_map($errorFormatter, $result->errors)
            ];
        } else {
            return [
                'data' => $result->data
            ];
        }
    }

    public function addTypes($types)
    {
        foreach ($types as $name => $type) {
            $this->addType($type, is_numeric($name) ? null : $name);
        }
    }

    public function addType($class, $name = null)
    {
        $name = $this->getTypeName($class, $name);
        $this->types[$name] = $class;

        event(new TypeAdded($class, $name));
    }

    public function addSchema($name, $schema)
    {
        $this->schemas[$name] = $schema;

        event(new SchemaAdded($schema, $name));
    }

    public function clearType($name)
    {
        if (isset($this->types[$name])) {
            unset($this->types[$name]);
        }
    }

    public function clearSchema($name)
    {
        if (isset($this->schemas[$name])) {
            unset($this->schemas[$name]);
        }
    }

    public function clearTypes()
    {
        $this->types = [];
    }

    public function clearSchemas()
    {
        $this->schemas = [];
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getSchemas()
    {
        return $this->schemas;
    }

    protected function clearTypeInstances()
    {
        $this->typesInstances = [];
    }

    protected function buildObjectTypeFromClass($type, $opts = [])
    {
        if (!is_object($type)) {
            $type = $this->app->make($type);
        }

        foreach ($opts as $key => $value) {
            $type->{$key} = $value;
        }

        return $type->toType();
    }

    protected function buildObjectTypeFromFields($fields, $opts = [])
    {
        $typeFields = [];
        foreach ($fields as $name => $field) {
            if (is_string($field)) {
                $field = $this->app->make($field);
                $name = is_numeric($name) ? $field->name : $name;
                $field->name = $name;
                $field = $field->toArray();
            } elseif (is_object($field)) {
                $field = $field->toArray();
            } else {
                $name = is_numeric($name) ? $field['name'] : $name;
                $field['name'] = $name;
            }
            $typeFields[$name] = $field;
        }

        return new ObjectType(array_merge([
            'fields' => $typeFields
        ], $opts));
    }

    protected function getTypeName($class, $name = null)
    {
        if ($name) {
            return $name;
        }

        $type = is_object($class) ? $class : $this->app->make($class);

        return $type->name;
    }

    public static function formatError(Error $e)
    {
        $error = [
            'message' => $e->getMessage()
        ];

        $locations = $e->getLocations();
        if (!empty($locations)) {
            $error['locations'] = array_map(function ($loc) {
                return $loc->toArray();
            }, $locations);
        }

        $previous = $e->getPrevious();
        if ($previous && $previous instanceof ValidationError) {
            $error['validation'] = $previous->getValidatorMessages();
        }

        return $error;
    }

    protected function buildDefaultSchema()
    {
        $queries = [
            'getServices'     => new BaseListQuery([
                'name'    => 'getServices',
                'type'    => 'service',
                'args'    => [
                    'type' => ['name' => 'type', 'type' => Type::string()],
                    'group' => ['name' => 'group', 'type' => Type::string()],
                ],
                'resolve' => function ($root, $args, $context, ResolveInfo $info) {
                    $request = new ServiceRequest();
                    Log::info('[REQUEST]', [
                        'API Version' => 'v2',
                        'Method'      => Verbs::GET,
                        'Service'     => null,
                        'Resource'    => null
                    ]);

                    Log::debug('[REQUEST]', [
                        'Parameters' => json_encode($args, JSON_UNESCAPED_SLASHES),
                        'API Key'    => $request->getHeader('X_DREAMFACTORY_API_KEY'),
                        'JWT'        => $request->getHeader('X_DREAMFACTORY_SESSION_TOKEN')
                    ]);

                    $selection = $info->getFieldSelection($depth = 1);
                    $fields = array_keys($selection);
                    $group = array_get($args, 'group');
                    $type = array_get($args, 'type');
                    if (!empty($group)) {
                        $results = ServiceManager::getServiceListByGroup($group, $fields, true);
                    } elseif (!empty($type)) {
                        $results = ServiceManager::getServiceListByType($type, $fields, true);
                    } else {
                        $results = ServiceManager::getServiceList($fields, true);
                    }
                    $services = [];
                    foreach ($results as $info) {
                        // only allowed services by role here
                        if (Session::allowsServiceAccess(array_get($info, 'name'))) {
                            $services[] = $info;
                        }
                    }

                    return $services;
                },
            ]),
            'getServiceTypes' => new BaseListQuery([
                'name'    => 'getServiceTypes',
                'type'    => 'service_type',
                'args'    => [
                    'group' => ['name' => 'group', 'type' => Type::string()],
                ],
                'resolve' => function ($root, $args, $context, ResolveInfo $info) {
                    $request = new ServiceRequest();
                    Log::info('[REQUEST]', [
                        'API Version' => 'v2',
                        'Method'      => Verbs::GET,
                        'Service'     => null,
                        'Resource'    => null
                    ]);

                    Log::debug('[REQUEST]', [
                        'Parameters' => json_encode($args, JSON_UNESCAPED_SLASHES),
                        'API Key'    => $request->getHeader('X_DREAMFACTORY_API_KEY'),
                        'JWT'        => $request->getHeader('X_DREAMFACTORY_SESSION_TOKEN')
                    ]);

                    $types = [];
                    $result = ServiceManager::getServiceTypes(array_get($args, 'group'));
                    foreach ($result as $type) {
                        $types[] = (object)$type->toArray();
                    }
                    Log::info('[RESPONSE]', ['Status Code' => 200, 'Content-Type' => 'application/json']);

                    return $types;
                },
            ]),
        ];
        $types = [
            'service'      => new BaseType([
                'name'        => 'service',
                'description' => 'A service',
                'fields'      => [
                    'id'          => [
                        'type'        => Type::int(),
                        'description' => 'The id of the service'
                    ],
                    'name'        => [
                        'type'        => Type::string(),
                        'description' => 'The name of the service'
                    ],
                    'label'       => [
                        'type'        => Type::string(),
                        'description' => 'The name of the service'
                    ],
                    'type'        => [
                        'type'        => Type::string(),
                        'description' => 'The type of the service'
                    ],
                    'description' => [
                        'type'        => Type::string(),
                        'description' => 'The name of the service'
                    ],
                ],
            ]),
            'service_type' => new BaseType(ServiceType::getSchema()),
        ];

        return ['query' => $queries, 'types' => $types];
    }
}
