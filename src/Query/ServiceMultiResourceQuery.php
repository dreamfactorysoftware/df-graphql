<?php

namespace DreamFactory\Core\GraphQL\Query;

use DreamFactory\Core\Components\Service2ServiceRequest;
use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Utility\ResourcesWrapper;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL;
use ServiceManager;

class ServiceMultiResourceQuery extends ServiceSingleResourceQuery
{
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
        $response = ResourcesWrapper::unwrapResources($content);

        return $response;
    }
}