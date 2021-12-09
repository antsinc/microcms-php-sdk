<?php

namespace Microcms\Test;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Microcms\Client;
use PHPUnit\Framework\TestCase;

final class ClientAsyncTest extends TestCase
{
    private $handlerStack;
    private $mock;
    private $container;

    protected function setUp(): void
    {
        $this->handlerStack = HandlerStack::create();
        $this->container = [];

        $this->mock = new MockHandler([]);
        $this->handlerStack->setHandler($this->mock);

        $history = Middleware::history($this->container);
        $this->handlerStack->push($history);
    }

    public function testList(): void
    {
        $this->mock->append(
            new Response(
                200,
                [],
                <<<JSON
                    {
                        "contents": [
                            {
                                "id": "my-content-id",
                                "createdAt": "2021-10-26T01:55:09.701Z",
                                "updatedAt": "2021-10-26T01:55:09.701Z",
                                "publishedAt": "2021-10-26T01:55:09.701Z",
                                "revisedAt": "2021-10-26T01:55:09.701Z",
                                "title": "foo",
                                "body": "Hello, microCMS!"
                            }
                        ],
                        "totalCount": 1,
                        "offset": 0,
                        "limit": 10
                    }
                JSON
            ));
        $this->mock->append(
            new Response(
                200,
                [],
                <<<JSON
                    {
                        "id": "my-content-id",
                        "createdAt": "2021-10-26T01:55:09.701Z",
                        "updatedAt": "2021-10-26T01:55:09.701Z",
                        "publishedAt": "2021-10-26T01:55:09.701Z",
                        "revisedAt": "2021-10-26T01:55:09.701Z",
                        "title": "foo",
                        "body": "Hello, microCMS!"
                    }
                JSON));

        $client = new Client("service", "key", new \GuzzleHttp\Client(['handler' => $this->handlerStack]));
        $results = $client->batchCall([
            [
                'action' => 'list',
                'endpoint' => "blog",
            ],
            [
                'action' => 'get',
                'endpoint' => "blog",
                'contentId' => "my-content-id",
            ],
        ]);

        $i = 0;
        $result = $results[$i];
        $this->assertCount(2, $this->container);
        $request = $this->container[$i]['request'];
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("https://service.microcms.io/api/v1/blog", (string)$request->getUri());
        $this->assertEquals("key", $request->getHeader('X-MICROCMS-API-KEY')[0]);

        $this->assertCount(1, $result->contents);
        $this->assertEquals("my-content-id", $result->contents[0]->id);
        $this->assertEquals(1, $result->totalCount);
        $this->assertEquals(0, $result->offset);
        $this->assertEquals(10, $result->limit);

        $i++;
        $result = $results[$i];
        $request = $this->container[$i]['request'];
        $this->assertEquals("GET", $request->getMethod());
        $this->assertEquals("https://service.microcms.io/api/v1/blog/my-content-id", (string)$request->getUri());
        $this->assertEquals("key", $request->getHeader('X-MICROCMS-API-KEY')[0]);

        $this->assertEquals("my-content-id", $result->id);
    }

}
