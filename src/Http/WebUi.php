<?php


namespace Monyxie\Wethook\Http;


use League\Plates\Engine as TemplateEngine;
use Monyxie\Wethook\Driver\Registry;
use Monyxie\Wethook\Task\Result;
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

    public function addRoutes(Router $router)
    {
        $router->addRoute('GET', '/', [$this, 'actionIndex']);
        $router->addRoute('GET', '/favicon.ico', [$this, 'actionFavicon']);
    }

    /**
     * @internal
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function actionIndex(ServerRequestInterface $request, ResponseInterface $response)
    {
        $drivers = [];
        foreach ($this->registry as $driver) {
            $drivers [] = $driver->getIdentifier();
        }

        $loopClassSegments = explode('\\', get_class($this->loop));
        $loopClass = end($loopClassSegments);

        $data = [
            'fields' => [
                [
                    'name' => 'Loop Class',
                    'title' => '',
                    'value' => $loopClass,
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
            ],
            'results' => array_map(function (Result $result) {
                $task = $result->getTask();
                return [
                    'startTime' => date('Y-m-d H:i:s', $result->getStartTime()),
                    'finishTime' => date('Y-m-d H:i:s', $result->getFinishTime()),
                    'command' => $task->getCommand(),
                    'workingDirectory' => $task->getWorkingDirectory(),
                    'exitCode' => $result->getExitCode(),
                    'output' => $result->getOutput(),
                    'outputBrief' => $this->truncateOutput($result->getOutput()),
                ];
            }, array_reverse($this->runner->getResults())),
        ];
        return $response->withBody(stream_for($this->engine->render('index', $data)));
    }

    public function actionFavicon(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Content-Type', 'image/png')
            ->withBody(stream_for(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAeElEQVQ4y61TWw6AIAxrF4+u564fhgShQ0GX8MEebVkDJcEFSVuQxFufA8iGHVAH0A7XjK4WM3LbOwBspTBiHkVgMQpBzDC6p8aK7Lovnha3tAMHkjllAXhAb+R/ciEFKOwjFb8qgKTuYJe6HKCr/Z5n9p0zF9olniImdOsXukmPAAAAAElFTkSuQmCC')));
    }

    private function formatMemory($size)
    {
        $unit = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    private function truncateOutput(string $output)
    {
        $output = trim($output);
        $linebreak = mb_strpos($output, "\n");
        if ($linebreak > 0) {
            $numKeep = $linebreak;
        } else {
            $numKeep = 50;
        }

        $len = mb_strlen($output);
        if ($len <= $numKeep) {
            return $output;
        }

        $output = mb_substr($output, 0, $numKeep);
        $output .= '...(' . ($len - $numKeep) . ' more)';
        return trim($output);
    }
}