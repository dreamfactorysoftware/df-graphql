<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Components\Service2ServiceRequest;
use DreamFactory\Core\Enums\Verbs;
use DreamFactory\Core\Exceptions\RestException;
use GraphQL\Type\Definition\ResolveInfo;
use ServiceManager;

class ServiceSingleResourceQuery extends BaseQuery
{
    /** @var string */
    protected $service;
    /** @var string */
    protected $resource;
    /** @var string */
    protected $verb;

    public function __construct($attributes = [])
    {
        $this->service = array_get($attributes, 'service');
        $this->resource = array_get($attributes, 'resource');
        $this->verb = array_get($attributes, 'verb', Verbs::GET);

        parent::__construct(array_except($attributes, ['service', 'resource', 'verb']));
    }

    /**
     * @param             $root
     * @param             $args
     * @param             $context
     * @param ResolveInfo $info
     * @return array
     * @throws RestException
     * @throws \Exception
     */
    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        $selection = $this->getFieldSelection($info);
        $params = array_merge($args, $selection);
        $request = new Service2ServiceRequest($this->verb, $params);
        $response = ServiceManager::handleServiceRequest($request, $this->service, $this->resource);
        $status = $response->getStatusCode();
        $content = $response->getContent();
        if ($status >= 300) {
            if (isset($content, $content['error'])) {
                $error = $content['error'];
                extract($error);
                /** @noinspection PhpUndefinedVariableInspection */
                throw new RestException($status, $message, $code);
            }

            throw new RestException($status, 'GraphQL query failed but returned invalid format.');
        }
        $response = $content;

        return $response;
    }

    protected function getFieldSelection(ResolveInfo $info)
    {
        $fields = [];
        $selection = $info->getFieldSelection($depth = 1);
        foreach ($selection as $key => $value) {
            $fields[] = $key;
        }

        return ['fields' => $fields];
    }
}