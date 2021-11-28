<?php
namespace Ostoandel\Log;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class CakeLogHandler implements HandlerInterface
{
    protected $logger;
    protected $levels = [];
    protected $channels = [];

    public function __construct(\CakeLogInterface $logger, array $levels, array $channels)
    {
        $this->logger = $logger;
        $this->channels = $channels;
        foreach ($levels as $level) {
            try {
                $this->levels[] = Logger::toMonologLevel($level);
            } catch (\InvalidArgumentException $e) {
                $this->levels[] = Logger::toMonologLevel('error');
                $this->channels[] = $level;
            }
        }
    }

    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function isHandling(array $record): bool
    {
        return (in_array($record['level'], $this->levels) || $this->levels === []);
    }

    public function handle(array $record): bool
    {
        if (in_array($record['channel'], $this->channels) || $this->channels === []) {
            $this->logger->write(Logger::getLevelName($record['level']), $record['message']);
        }
        return false;
    }

    public function close(): void
    {
    }

}