<?php


namespace Monyxie\Wethook\Http;


use League\Plates\Engine as TemplateEngine;
use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Task\Runner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use function RingCentral\Psr7\stream_for;

class WebUi
{
    /**
     * @var TemplateEngine
     */
    private $engine;
    /**
     * @var Runner
     */
    private $runner;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * WebUi constructor.
     * @param TemplateEngine $engine
     * @param Runner $runner
     * @param Registry $registry
     * @param LoopInterface $loop
     */
    public function __construct(TemplateEngine $engine, Runner $runner, Registry $registry, LoopInterface $loop)
    {
        $this->engine = $engine;
        $this->runner = $runner;
        $this->registry = $registry;
        $this->loop = $loop;
    }

    public function addRoutes(Router $router) {
        $router->addRoute('GET', '/', [$this, 'actionIndex']);
    }

    /**
     * @internal
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function actionIndex(ServerRequestInterface $request, ResponseInterface $response) {
        $drivers = [];
        foreach ($this->registry as $driver) {
            $drivers []= $driver->getIdentifier();
        }

        $data = [
            'fields' => [
                [
                    'name' => 'Loop Class',
                    'title' => '',
                    'value' => end(explode('\\', get_class($this->loop))),
                ],
                [
                    'name' => 'Registered Drivers',
                    'title' => '',
                    'value' => join(', ', $drivers),
                ],
                [
                    'name' => 'Runner Status',
                    'title' => '',
                    'value' => $this->runner->isRunning() ? 'busy' : 'idle',
                ],
                [
                    'name' => 'Enqueued Tasks',
                    'title' => '',
                    'value' => $this->runner->getNumEnqueued(),
                ],
                [
                    'name' => 'Finished Tasks',
                    'title' => '',
                    'value' => $this->runner->getNumFinished(),
                ],
                [
                    'name' => 'Latest Enqueued',
                    'title' => '',
                    'value' => $this->runner->getLatestEnqueuedAt()
                        ? date('Y-m-d H:i:s', $this->runner->getLatestEnqueuedAt())
                        : '-',
                ],
                [
                    'name' => 'Latest Finished',
                    'title' => '',
                    'value' => $this->runner->getLatestFinishedAt()
                        ? date('Y-m-d H:i:s', $this->runner->getLatestFinishedAt())
                        : '-',
                ],
                [
                    'name' => 'Mem Allocated',
                    'title' => '',
                    'value' => $this->formatMemory(memory_get_usage(true)),
                ],
                [
                    'name' => 'Mem Usage',
                    'title' => '',
                    'value' => $this->formatMemory(memory_get_usage(false)),
                ],
                [
                    'name' => 'Running Since',
                    'title' => '',
                    'value' => date('Y-m-d H:i:s', STARTUP_TIME),
                ],
            ]
        ];
        return $response->withBody(stream_for($this->engine->render('index', $data)));
    }

    private function formatMemory($size)
    {
        $unit = array('B','KiB','MiB','GiB','TiB','PiB');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}