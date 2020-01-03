<?php declare(strict_types=1);

namespace SwoftLabs\ReleaseCli;

use RuntimeException;
use Swoole\Event;
use Swoole\Process;

/**
 * Class ProcessPool2
 *
 * @package SwoftLabs\ReleaseCli
 */
class ProcessPool2 extends AbstractPool
{
    /**
     * @var int
     */
    private $workerId = 0;

    /**
     * @var int
     */
    private $workerNum;

    /**
     * @var bool
     */
    private $coroutine;

    /**
     * @var bool
     */
    private $redirectIO;

    /**
     * @link https://wiki.swoole.com/wiki/page/289.html
     * @var int
     */
    private $msgQueueKey;

    /**
     * @var bool
     */
    private $keepalive = false;

    /**
     * @var Process[]
     */
    private $workers;

    /**
     * @param int  $workerNum
     * @param int  $msgQueueKey
     * @param bool $redirectIO
     * @param bool $enableCoroutine
     *
     * @return static
     */
    public static function new(
        int $workerNum,
        int $msgQueueKey = 0,
        bool $redirectIO = false,
        bool $enableCoroutine = true
    ): self {
        return new self($workerNum, $msgQueueKey, $redirectIO, $enableCoroutine);
    }

    /**
     * Class constructor.
     * doc see https://wiki.swoole.com/wiki/page/214.html
     *
     * @param int  $workerNum
     * @param int  $msgQueueKey
     * @param bool $redirectIO
     * @param bool $enableCoroutine
     */
    public function __construct(
        int $workerNum,
        int $msgQueueKey = 0,
        bool $redirectIO = false,
        bool $enableCoroutine = true
    ) {
        $this->workerNum   = $workerNum;
        $this->msgQueueKey = $msgQueueKey;
        $this->redirectIO  = $redirectIO;
        $this->coroutine   = $enableCoroutine;
    }

    public function start(): void
    {
        if (!$fn = $this->startHandler) {
            throw new RuntimeException('the worker start handler is required before start');
        }

        for ($i = 0; $i < $this->workerNum; $i++) {
            $proc = new Process(function (Process $proc) use ($fn, $i) {
                $this->workerId = $i;

                $fn($this, $i);
            }, $this->redirectIO, 0, $this->coroutine);

            if ($this->msgQueueKey) {
                $proc->useQueue($this->msgQueueKey);
            }

            $proc->start();
            $this->workers[] = $proc;
        }

        // SIGCHLD = 17
        Process::signal(17, function($signal) {
            // 必须为false，非阻塞模式
            while($ret =  Process::wait(false)) {
                echo "PID={$ret['pid']}\n";

                // on stop
                if ($stopFunc = $this->stopHandler) {
                    $stopFunc($this, $this->workerId);
                }
            }
        });
        // Process::wait();

        Event::wait();
    }

    /**
     * @param int $workerId
     *
     * @return Process
     */
    public function getProcess(int $workerId = -1): Process
    {
        // return current worker
        if ($workerId < 0) {
            return $this->workers[$this->workerId];
        }

        return $this->workers[$workerId];
    }
}
