<?php

declare(strict_types=1);

namespace Greenplugin\TelegramBot\Tests\Method;

use Greenplugin\TelegramBot\ApiClientInterface;
use Greenplugin\TelegramBot\BotApi;

abstract class MethodTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param $methodName
     * @param $request
     * @param array $result
     *
     * @return BotApi
     */
    protected function getBot($methodName, $request, $result = []): BotApi
    {
        $stub = $this->getMockBuilder(ApiClientInterface::class)
            ->getMock();

        $stub->expects($this->once())
            ->method('send')
            ->with($methodName, $request)
            ->willReturn((object) (['ok' => true, 'result' => $result]));

        return new BotApi('000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $stub);
    }

    /**
     * @param $methodName
     * @param $request
     * @param array $fileMap
     * @param array $serializableFields
     * @param array $result
     *
     * @return BotApi
     */
    protected function getBotWithFiles(
        $methodName,
        $request,
        array $fileMap,
        array $serializableFields = [],
        $result = []
    ): BotApi {
        $requestedData = [];
        $stub = $this->getMockBuilder(ApiClientInterface::class)
            ->getMock();

        $stub->expects($this->once())
            ->method('send')
            ->with(
                $methodName,
                $this->callback(function ($query) use (&$requestedData) {
                    $requestedData = $query;

                    return true;
                }),
                $this->callback(function ($files) use (&$requestedData, $request, $fileMap, $serializableFields) {
                    $request = $this->buildFileTree($files, $request, $fileMap);
                    foreach ($serializableFields as $field) {
                        $request[$field] = \json_encode($request[$field]);
                    }
                    $this->assertEquals($request, $requestedData);

                    return true;
                })
            )
            ->willReturn((object) (['ok' => true, 'result' => $result]));

        return new BotApi('000000000:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $stub);
    }

    /**
     * @param array $files
     * @param array $request
     * @param array $map
     * @param int   $pointer
     *
     * @return array
     */
    private function buildFileTree($files, $request, $map, $pointer = 0): array
    {
        foreach ($map as $key => $field) {
            if (\is_array($field)) {
                $request[$key] = $this->buildFileTree($files, $request, $field, $pointer);
            } else {
                $request[$key] = 'attach://' . \array_keys($files)[$pointer];
                ++$pointer;
            }
        }

        return $request;
    }
}
