<?php

namespace Ackintosh\Ganesha\Storage\Adapter;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage;
use Ackintosh\Ganesha\Storage\AdapterInterface;

class MongoDB implements AdapterInterface, TumblingTimeWindowInterface, SlidingTimeWindowInterface
{
    /**
     * @var \MongoDB\Driver\Manager
     */
    private $manager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string
     */
    private $collectionName;

    /**
     * @var string
     */
    private $dbName;

    /**
     * MongoDB constructor.
     * @param \MongoDB\Driver\Manager $manager
     */
    public function __construct(\MongoDB\Driver\Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return bool
     */
    public function supportCountStrategy()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function supportRateStrategy()
    {
        return true;
    }

    /**
     * @param Configuration $configuration
     * @return void
     * @throws \Exception
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->dbName = $this->configuration->offsetGet('dbName');
        $this->collectionName = $this->configuration->offsetGet('collectionName');
    }

    /**
     * @param string $service
     * @return int
     * @throws StorageException
     */
    public function load($service)
    {
        $cursor = $this->read(['service' => $service]);
        $result = $cursor->toArray();
        if ($result === null || empty($result)) {
            $this->update(['service' => $service], ['$set' => ['count' => 0]]);
            return 0;
        }
        if (!isset($result[0]['count'])) {
            throw new StorageException('failed to load service : file "count" not found.');
        }

        return $result[0]['count'];
    }

    /**
     * @param string $service
     * @param int $count
     * @return void
     * @throws StorageException
     */
    public function save($service, $count)
    {
        $this->update(['service' => $service], ['$set' => ['count' => $count]]);
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function increment($service)
    {
        $this->update(['service' => $service], ['$inc' => ['count' => 1]], ['safe' => true]);
    }

    /**
     * @param string $service
     * @return void
     * @throws StorageException
     */
    public function decrement($service)
    {
        $this->update(['service' => $service], ['$inc' => ['count' => -1]], ['safe' => true]);
    }

    /**
     * @param string $service
     * @param int $lastFailureTime
     * @throws StorageException
     */
    public function saveLastFailureTime($service, $lastFailureTime)
    {
        $this->update(['service' => $service], ['$set' => ['lastFailureTime' => $lastFailureTime]]);
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadLastFailureTime($service)
    {
        $cursor = $this->read(['service' => $service]);
        $result = $cursor->toArray();
        if ($result === null || empty($result)) {
            throw new StorageException('failed to last failure time : entry not found.');
        }
        if (!isset($result[0]['lastFailureTime'])) {
            throw new StorageException('failed to last failure time : field "lastFailureTime" not found.');
        }

        return $result[0]['lastFailureTime'];
    }

    /**
     * @param string $service
     * @param int $status
     * @throws StorageException
     */
    public function saveStatus($service, $status)
    {
        $this->update(['service' => $service], ['$set' => ['status' => $status]]);
    }

    /**
     * @param  string $service
     * @return int
     * @throws StorageException
     */
    public function loadStatus($service)
    {
        $cursor = $this->read(['service' => $service]);
        $result = $cursor->toArray();

        if ($result === null || empty($result) || !isset($result[0]['status'])) {
            $this->saveStatus($service, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return $result[0]['status'];
    }

    public function reset()
    {
        $this->delete([], []);
    }

    /**
     * @return string "db.collectionName"
     */
    private function getNamespace()
    {
        return $this->dbName . '.' . $this->collectionName;
    }

    /**
     * @param $filter
     * @param array $queryOptions
     * @return \MongoDB\Driver\Cursor
     */
    private function read($filter, array $queryOptions = [])
    {
        try {
            $query = new \MongoDB\Driver\Query($filter, $queryOptions);
            $cursor = $this->manager->executeQuery($this->getNamespace(), $query);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
            return $cursor;
        } catch (\MongoDB\Driver\Exception\Exception $ex) {
            throw new StorageException('adapter error : ' . $ex->getMessage());
        }
    }

    /**
     * @param $filter
     * @param array $deleteOptions
     * @return void
     */
    private function delete($filter, array $deleteOptions = [])
    {
        $this->bulkWrite($filter, $options = ['deleteOptions' => $deleteOptions], 'delete');
    }

    /**
     * @param $filter
     * @param $newObj
     * @param array $updateOptions
     */
    private function update($filter, $newObj, array $updateOptions = ['multi' => false, 'upsert' => true])
    {
        $this->bulkWrite($filter, $options = ['newObj' => $newObj, 'updateOptions' => $updateOptions], 'update');
    }

    /**
     * @param $filter
     * @param array $options
     * @param string $command
     */
    private function bulkWrite($filter, array $options, $command)
    {
        try {
            $bulk = new \MongoDB\Driver\BulkWrite();
            switch ($command) {
                case 'update':
                    if (isset($options['newObj']['$set'])) {
                        $options['newObj']['$set']['date'] = new \MongoDB\BSON\UTCDateTime();
                    }
                    $bulk->update($filter, $options['newObj'], $options['updateOptions']);
                    break;
                case 'delete':
                    $bulk->delete($filter, $options['deleteOptions']);
                    break;
            }
            $writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 100);
            $result = $this->manager->executeBulkWrite($this->getNamespace(), $bulk, $writeConcern);
            if (!empty($result->getWriteErrors())) {
                $errorMessage = '';
                foreach ($result->getWriteErrors() as $writeError) {
                    $errorMessage .= 'Operation#' . $writeError->getIndex() . ': ' . $writeError->getMessage() . ' (' . $writeError->getCode() . ')' . "\n";
                }
                throw new StorageException('failed '.$command.' the value : ' . $errorMessage);
            }
        } catch (\MongoDB\Driver\Exception\Exception $ex) {
            throw new StorageException('adapter error : ' . $ex->getMessage());
        }
    }
}
