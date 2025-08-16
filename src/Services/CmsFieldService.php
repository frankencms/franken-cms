<?php

namespace FrankenCms\Services;

class CmsFieldService
{
    public function handle($params)
    {

        $params = json_decode($params);

        dump($params);

        // If passed as single object
        if (is_object($params)) {
            // Handle single object
            return $this->processObject($params);
        }

        // If passed as array
        if (is_array($params)) {
            // Handle array of parameters
            $object = $params['object'] ?? null;
            return $this->processObject($object);
        }

        // If passed as JSON
        $decodedParams = json_decode($params);
        return $this->processObject($decodedParams);
    }

    private function processObject($object)
    {

        dump($object);

        // Your custom logic here
        return 'nul';
    }
}
