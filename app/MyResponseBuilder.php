<?php

namespace App;

class MyResponseBuilder extends \MarcinOrlowski\ResponseBuilder\ResponseBuilder
{
    protected function buildResponse(
        bool $success,
        int $api_code,
        $msg_or_api_code,
        array $placeholders = null,
        $data = null,
        array $debug_data = null
    ): array {
        // tell ResponseBuilder to do all the heavy lifting first
        $response = parent::buildResponse($success, $api_code, $msg_or_api_code, $placeholders, $data, $debug_data);

        // then do all the tweaks you need
        $date = new \DateTime();
        $response['timestamp'] = $date->getTimestamp();
        $response['timezone'] = $date->getTimezone();

        unset($response['locale']);


        // finally, return what $response holds
        return $response;
    }
}
