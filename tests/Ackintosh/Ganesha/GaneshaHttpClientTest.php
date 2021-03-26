<?php

namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\HttpClient\FailureDetectorInterface;
use Ackintosh\Ganesha\HttpClient\RestFailureDetector;
use Ackintosh\Ganesha\Storage\Adapter\Memcached;
use Ackintosh\Ganesha\Storage\Adapter\Redis;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Ackintosh\Ganesha\GaneshaHttpClient
 */
class GaneshaHttpClientTest extends TestCase
{
    /**
     * @var Redis
     */
    private $adapter;

    protected function setUp(): void
    {
        if (!\extension_loaded('redis')) {
            self::markTestSkipped('No ext-redis present');
        }

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
     */
    public function recordsSuccessOn200(): void
    {
        $httpResponse = new MockResponse('', ['http_code' => 200]);
        $client = $this->buildClient(null, [$httpResponse]);

        $response = $client->request('GET', 'http://api.example.com/awesome_resource/200');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            1,
            $this->adapter->load(
                Storage\StorageKeys::KEY_PREFIX.'api.example.com'.Storage\StorageKeys::KEY_SUFFIX_SUCCESS
            )
        );
    }

    /**
     * @test
     */
    public function recordsSuccessOn400(): void
    {
        $httpResponse = new MockResponse('', ['http_code' => 400]);
        $client = $this->buildClient(null, [$httpResponse]);

        $client->request('GET', 'http://api.example.com/awesome_resource/400');

        self::assertSame(
            1,
            $this->adapter->load(
                Storage\StorageKeys::KEY_PREFIX.'api.example.com'.Storage\StorageKeys::KEY_SUFFIX_SUCCESS
            )
        );
    }

    /**
     * @test
     */
    public function recordsSuccessOn500(): void
    {
        $httpResponse = new MockResponse('', ['http_code' => 500]);
        $client = $this->buildClient(null, [$httpResponse]);

        $client->request('GET', 'http://api.example.com/awesome_resource/500');

        self::assertSame(
            1,
            $this->adapter->load(
                Storage\StorageKeys::KEY_PREFIX.'api.example.com'.Storage\StorageKeys::KEY_SUFFIX_SUCCESS
            )
        );
    }

    /**
     * @test
     */
    public function recordsFailureOnConfiguredHttpStatusCodeAtRequestLevel(): void
    {
        $httpResponse = new MockResponse('', ['http_code' => 503]);
        $client = $this->buildClient(null, [$httpResponse], new RestFailureDetector());

        $client->request(
            'GET',
            'http://api.example.com/awesome_resource/503',
            [
                Ganesha\HttpClient\RestFailureDetector::OPTION_KEY => [503],
            ]
        );

        self::assertSame(
            1,
            $this->adapter->load(
                Storage\StorageKeys::KEY_PREFIX.'api.example.com'.Storage\StorageKeys::KEY_SUFFIX_FAILURE
            )
        );
    }

    /**
     * @test
     */
    public function recordsFailureOnConfiguredHttpStatusCodeAtClientLevel(): void
    {
        $httpResponse = new MockResponse('', ['http_code' => 503]);
        $client = $this->buildClient(
            null,
            [$httpResponse],
            new RestFailureDetector([503])
        );

        $client->request('GET', 'http://api.example.com/awesome_resource/503');

        self::assertSame(
            1,
            $this->adapter->load(
                Storage\StorageKeys::KEY_PREFIX.'api.example.com'.Storage\StorageKeys::KEY_SUFFIX_FAILURE
            )
        );
    }

    /**
     * @test
     */
    public function recordsFailureOnRequestTimedOut(): void
    {
        if (!getenv('RUN_IN_DOCKER_COMPOSE')) {
            self::markTestSkipped(
                'This test can only run in docker-compose provided in this repository, as it depends on server which causes a time-out error.'
            );
        }

        $client = $this->buildClient();

        $requestTimedOut = false;
        try {
            // Server takes 10secs, so it always times out.
            // @see examples/server/timeout.php
            $response = $client->request('GET', 'http://server/server/timeout.php', ['max_duration' => 3]);
            $response->getHeaders();
        } catch (TransportExceptionInterface $e) {
            $requestTimedOut = true;
        }

        if (!$requestTimedOut) {
            self::fail('The request did not time out, so the test can not be continued.');
        }

        self::assertSame(
            1,
            $this->adapter->load(Storage\StorageKeys::KEY_PREFIX.'server'.Storage\StorageKeys::KEY_SUFFIX_FAILURE)
        );
    }

    /**
     * @test
     */
    public function recordsFailureOnRedirectionLoop(): void
    {
        if (!getenv('RUN_IN_DOCKER_COMPOSE')) {
            self::markTestSkipped(
                'This test can only run in docker-compose provided in this repository, as it depends on server which causes redirection loop.'
            );
        }

        $client = $this->buildClient();

        $redirectionLoop = false;
        try {
            // Server will redirect 4 times before responding a 200
            // @see examples/server/redirect.php
            $response = $client->request(
                'GET',
                'http://server/server/redirect.php?redirects=4',
                ['max_redirects' => 3]
            );
            $response->getHeaders();
        } catch (RedirectionExceptionInterface $e) {
            $redirectionLoop = true;
        }

        if (!$redirectionLoop) {
            self::fail('The request did not end in redirection loop, so the test can not be continued.');
        }

        self::assertSame(
            1,
            $this->adapter->load(Storage\StorageKeys::KEY_PREFIX.'server'.Storage\StorageKeys::KEY_SUFFIX_FAILURE)
        );
    }

    /**
     * @test
     */
    public function reject(): void
    {
        // Build Ganesha which has count strategy with memcached adapter
        $m = new \Memcached();
        $m->addServer(
            getenv('GANESHA_EXAMPLE_MEMCACHED') ? getenv('GANESHA_EXAMPLE_MEMCACHED') : 'localhost',
            11211
        );
        $m->flush();
        $ganesha = Builder::withCountStrategy()
            ->failureCountThreshold(3)
            ->adapter(new Memcached($m))
            ->intervalToHalfOpen(10)
            ->build();
        // Setup a client
        $client = $this->buildClient($ganesha);

        $service = 'api.example.com';
        $ganesha->failure($service);
        $ganesha->failure($service);
        $ganesha->failure($service);

        $this->expectException(RejectedException::class);
        $client->request('GET', 'http://'.$service.'/awesome_resource');
    }

    /**
     * @test
     */
    public function doNotPropagateGaneshaOptionToDecoratedInstance(): void
    {
        $failureDetectorOptionKeys = ['foo_option_key', 'bar_option_key'];
        $failureDetector = $this->createMock(FailureDetectorInterface::class);
        $failureDetector->method('getOptionKeys')->willReturn($failureDetectorOptionKeys);

        $decoratedClient = $this->createMock(HttpClientInterface::class);
        $client = new GaneshaHttpClient($decoratedClient, $this->buildGanesha(), null, $failureDetector);

        $decoratedClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'http://api.example.com/awesome_resource', ['max_duration' => 3.0]);

        $client->request(
            'GET',
            'http://api.example.com/awesome_resource',
            \array_merge(
                array_combine($failureDetectorOptionKeys, ['foo_option_value', 'bar_option_value']),
                [
                    'max_duration' => 3.0,
                    Ganesha\HttpClient\ServiceNameExtractor::OPTION_KEY => 'an_awesome_service',
                ]
            )
        );
    }

    /**
     * @test
     */
    public function streamDelegatesToDecoratedInstance(): void
    {
        $decoratedClient = $this->createMock(HttpClientInterface::class);
        $client = new GaneshaHttpClient($decoratedClient, $this->buildGanesha());

        $responses = $this->createMock(ResponseInterface::class);
        $timeout = 1.0;

        $decoratedClient
            ->expects(self::once())
            ->method('stream')
            ->with($responses, $timeout);

        $client->stream($responses, $timeout);
    }

    /**
     * @param MockResponse[] $responses
     */
    private function buildClient(
        ?Ganesha $ganesha = null,
        array $responses = [],
        ?FailureDetectorInterface $failureDetector = null
    ): HttpClientInterface {
        $client = (0 === \count($responses)) ? HttpClient::create() : new MockHttpClient($responses);

        return new GaneshaHttpClient($client, $ganesha ?? $this->buildGanesha(), null, $failureDetector);
    }

    private function buildGanesha(): Ganesha
    {
        return Builder::withRateStrategy()
            ->timeWindow(30)
            ->failureRateThreshold(50)
            ->minimumRequests(10)
            ->intervalToHalfOpen(5)
            ->adapter($this->adapter)
            ->build();
    }
}
