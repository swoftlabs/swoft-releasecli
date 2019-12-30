<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli;

use RuntimeException;
use Swoole\Process\Pool;
use function swoole_cpu_num;

/**
 * Class ProcessPool
 *
 * @package SwoftLabs\ReleaseCli
 */
class ProcessPool
{
    /**
     * @var Pool
     */
    private $pool;

    /**
     * Worker logic handler. eg:
     *
     * function (Swoole\Process\Pool $pool, int $workerId) {
     *      // do something
     * }
     *
     * @var callable
     */
    private $startHandler;

    /**
     * On worker stop handler. see $startHandler
     *
     * @var callable
     */
    private $stopHandler;

    /**
     * @param int  $workerNum
     * @param int  $ipcType
     * @param int  $msgQueueKey
     * @param bool $enableCoroutine
     *
     * @return static
     */
    public static function new(int $workerNum, int $ipcType = 0, int $msgQueueKey = 0, bool $enableCoroutine = true): self
    {
        return new self($workerNum, $ipcType, $msgQueueKey, $enableCoroutine);
    }

    /**
     * Class constructor.
     * doc see https://wiki.swoole.com/wiki/page/905.html
     *
     * @param int  $workerNum
     * @param int  $ipcType see SWOOLE_IPC_NONE, SWOOLE_IPC_UNIXSOCK, SWOOLE_IPC_SOCKET
     * @param int  $msgQueueKey
     * @param bool $enableCoroutine
     */
    public function __construct(int $workerNum, int $ipcType = 0, int $msgQueueKey = 0, bool $enableCoroutine = true)
    {
        $this->pool = new Pool($workerNum, $ipcType, $msgQueueKey, $enableCoroutine);
    }

    /**
     * @param callable $handler
     */
    public function onStart(callable $handler): void
    {
        $this->startHandler = $handler;
    }

    /**
     * @param callable $handler
     */
    public function onStop(callable $handler): void
    {
        $this->stopHandler = $handler;
    }

    public function start(): void
    {
        if (!$this->startHandler) {
            throw new RuntimeException('the worker start handler is required before start');
        }

        $this->pool->on('WorkerStart', $this->startHandler);

        if ($stopFunc = $this->stopHandler) {
            $this->pool->on('WorkerStop', $stopFunc);
        }

        $this->pool->start();
    }

    /**
     * @return int
     */
    public function getBestWorkerNum(): int
    {
        return (int)ceil(swoole_cpu_num() * 1.5);
    }

    /**
     * @return Pool
     */
    public function getPool(): Pool
    {
        return $this->pool;
    }

    /**
     * @param Pool $pool
     */
    public function setPool(Pool $pool): void
    {
        $this->pool = $pool;
    }

    /**
     * @return callable
     */
    public function getStartHandler(): callable
    {
        return $this->startHandler;
    }
}
