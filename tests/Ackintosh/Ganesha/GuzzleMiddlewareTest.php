<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\Adapter\Redis;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;

class GuzzleMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Cleanup test statistics before run tests
        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn200()
    {
        $redis = new \Redis();
        $redis->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $adapter = new Redis($redis);
        $ganesha = Builder::build([
            'timeWindow'            => 30,
            'failureRateThreshold'  => 50,
            'minimumRequests'       => 10,
            'intervalToHalfOpen'    => 5,
            'adapter'               => $adapter,
        ]);

        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);
        $client = new Client([
            'handler' => $handlers,
        ]);

        $response = $client->get('http://api.example.com/awesome_resource/200');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            1,
            $adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
        );
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn400()
    {
        $redis = new \Redis();
        $redis->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $adapter = new Redis($redis);
        $ganesha = Builder::build([
            'timeWindow'            => 30,
            'failureRateThreshold'  => 50,
            'minimumRequests'       => 10,
            'intervalToHalfOpen'    => 5,
            'adapter'               => $adapter,
        ]);

        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);
        $client = new Client([
            'handler' => $handlers,
        ]);

        try {
            $client->get('http://api.example.com/awesome_resource/400');
        } catch (ClientException $e) {
            // 4xx error has occured, it is as expected.
        }

        $this->assertSame(
            1,
            $adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
        );
    }

    /**
     * @test
     */
    public function recordsFailureOnRequestTimedOut()
    {
        if (!getenv('RUN_IN_DOCKER_COMPOSE')) {
            $this->markTestSkipped(
                'This test can only run in docker-compose provided in this repository, as it depends on server which causes a time-out error.'
            );
        }

        $redis = new \Redis();
        $redis->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $adapter = new Redis($redis);
        $ganesha = Builder::build([
            'timeWindow'            => 30,
            'failureRateThreshold'  => 50,
            'minimumRequests'       => 10,
            'intervalToHalfOpen'    => 5,
            'adapter'               => $adapter,
        ]);

        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);
        $client = new Client([
            'timeout' => 3,
            'handler' => $handlers,
        ]);

        $requestTimedOut = false;
        try {
            // Server takes 10secs, so it always times out.
            // @see examples/server/timeout.php
            $client->get('http://server/server/timeout.php');
        } catch (ConnectException $e) {
            $requestTimedOut = true;
        }

        if (!$requestTimedOut) {
            $this->fail('The request did not time out, so the test can not be continued.');
        }

        $this->assertSame(
            1,
            $adapter->load(Storage::KEY_PREFIX . 'server' . Storage::KEY_SUFFIX_FAILURE)
        );
    }
}