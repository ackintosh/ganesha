<?php
declare(strict_types=1);

namespace Ackintosh\Ganesha\Storage\Adapter;

use Predis\ClientInterface;
use Predis\Command\CommandInterface;

final class PredisRedisClientInterfaceTestDouble implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getProfile()
    {
        return $this->client->getProfile();
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->client->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $this->client->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->client->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->client->getConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function createCommand($commandID, $arguments = [])
    {
        return $this->client->createCommand($commandID, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->client->executeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($commandID, $arguments)
    {
        return $this->executeCommand(
            $this->createCommand($commandID, $arguments)
        );
    }
}
