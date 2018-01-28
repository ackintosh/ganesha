<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\Adapter\Memcached;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;

class GuzzleMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Redis
     */
    private $adapter;

    public function setUp()
    {
        parent::setUp();

        // Cleanup test statistics before run tests
        $redis = new \Redis();
        $redis->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $redis->flushAll();

        $this->adapter = new Redis($redis);
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn200()
    {
        $client = $this->buildClient();
        $response = $client->get('http://api.example.com/awesome_resource/200');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            1,
            $this->adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
        );
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn400()
    {
        $client = $this->buildClient();

        try {
            $client->get('http://api.example.com/awesome_resource/400');
        } catch (ClientException $e) {
            // 4xx error has occured, it is as expected.
        }

        $this->assertSame(
            1,
            $this->adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
        );
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn500()
    {
        $client = $this->buildClient();

        try {
            $client->get('http://api.example.com/awesome_resource/500');
        } catch (ServerException $e) {
            // 5xx error has occured, it is as expected.
        }

        $this->assertSame(
            1,
            $this->adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
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

        $client = $this->buildClient();

        $requestTimedOut = false;
        try {
            // Server takes 10secs, so it always times out.
            // @see examples/server/timeout.php
            $client->get('http://server/server/timeout.php', ['timeout' => 3]);
        } catch (ConnectException $e) {
            $requestTimedOut = true;
        }

        if (!$requestTimedOut) {
            $this->fail('The request did not time out, so the test can not be continued.');
        }

        $this->assertSame(
            1,
            $this->adapter->load(Storage::KEY_PREFIX . 'server' . Storage::KEY_SUFFIX_FAILURE)
        );
    }

    /**
     * @test
     * @expectedException \Ackintosh\Ganesha\Exception\RejectedException
     */
    public function reject()
    {
        // Build Ganesha which has count strategy with memcached adapter
        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $m->flush();
        $ganesha = Builder::buildWithCountStrategy([
            'failureCountThreshold' => 3,
            'adapter' => new Memcached($m),
            'intervalToHalfOpen' => 10,
        ]);
        // Setup a client
        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);
        $client = new Client(['handler' => $handlers]);

        $service = 'api.example.com';
        $ganesha->failure($service);
        $ganesha->failure($service);
        $ganesha->failure($service);

        $client->get('http://' . $service . '/awesome_resource');
    }

    /**
     * @return Client
     */
    private function buildClient()
    {
        $ganesha = Builder::build([
            'timeWindow'           => 30,
            'failureRateThreshold' => 50,
            'minimumRequests'      => 10,
            'intervalToHalfOpen'   => 5,
            'adapter'              => $this->adapter,
        ]);

        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);

        return new Client(['handler' => $handlers]);
    }
}