<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use PHPUnit\Framework\TestCase;

class MongoDBTest extends TestCase
{
    /**
     * @var MongoDB
     */
    private $mongodbAdapter;

    /**
     * @var string
     */
    private $dbName = 'ganesha';

    /**
     * @var string
     */
    private $collectionName = 'ganeshaCollection';

    /**
     * @var string
     */
    private $service = 'testService';

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        if (!\extension_loaded('mongodb')) {
            self::markTestSkipped('No ext-mongodb present');
        }

        $host = getenv('GANESHA_EXAMPLE_MONGO') ? getenv('GANESHA_EXAMPLE_MONGO') : 'localhost';
        $manager = new \MongoDB\Driver\Manager('mongodb://' . $host . ':27017/');

        $this->mongodbAdapter = new MongoDB($manager);

        $configuration = new Configuration(['dbName' => $this->dbName, 'collectionName' => $this->collectionName]);

        $this->mongodbAdapter->setConfiguration($configuration);
    }

    /**
     * @test
     */
    public function supportsCountStrategy()
    {
        $this->assertTrue($this->mongodbAdapter->supportCountStrategy());
    }

    /**
     * @test
     */
    public function supportsRateStrategy()
    {
        $this->assertTrue($this->mongodbAdapter->supportRateStrategy());
    }

    /**
     * @test
     */
    public function saveAndLoad()
    {
        $this->mongodbAdapter->save($this->service, 1);
        $this->assertSame(1, $this->mongodbAdapter->load($this->service));
    }

    /**
     * @test
     */
    public function increment()
    {
        $this->mongodbAdapter->increment($this->service);
        $this->assertSame(2, $this->mongodbAdapter->load($this->service));
    }

    /**
     * @test
     */
    public function decrement()
    {
        $this->mongodbAdapter->decrement($this->service);
        $this->assertSame(1, $this->mongodbAdapter->load($this->service));
    }

    /**
     * @test
     */
    public function saveAndLoadLastFailureTime()
    {
        $time = time();
        $this->mongodbAdapter->saveLastFailureTime($this->service, $time);
        $this->assertSame($time, $this->mongodbAdapter->loadLastFailureTime($this->service));
    }

    /**
     * @test
     */
    public function saveAndLoadStatus()
    {
        $status = Ganesha::STATUS_TRIPPED;
        $this->mongodbAdapter->saveStatus($this->service, $status);
        $this->assertSame($status, $this->mongodbAdapter->loadStatus($this->service));
    }

    /**
     * @test
     */
    public function resetAndLoad()
    {
        $this->mongodbAdapter->reset();
        $r = $this->mongodbAdapter->load($this->service);
        $this->assertSame(0, $r);
    }

    /**
     * @test
     */
    public function loadStatusIfNotFound()
    {
        $status = Ganesha::STATUS_CALMED_DOWN;
        $this->assertSame($status, $this->mongodbAdapter->loadStatus($this->service));
    }
}
