<?php


namespace Monyxie\Wethook\Http;


use League\Plates\Engine as TemplateEngine;
use Monyxie\Wethook\Monitor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function RingCentral\Psr7\stream_for;

class WebUi
{
    /**
     * @var TemplateEngine
     */
    private $engine;
    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * WebUi constructor.
     * @param TemplateEngine $engine
     * @param Monitor $monitor
     */
    public function __construct(TemplateEngine $engine, Monitor $monitor)
    {
        $this->engine = $engine;
        $this->monitor = $monitor;
    }

    /**
     * @param Router $router
     */
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
        $data = [
            'fields' => [
                [
                    'name' => 'Loop Class',
                    'title' => '',
                    'value' => $this->monitor->getLoopClass(),
                ],
                [
                    'name' => 'Registered Drivers',
                    'title' => '',
                    'value' => join(', ', $this->monitor->getRegisteredDrivers()),
                ],
                [
                    'name' => 'Runner Status',
                    'title' => '',
                    'value' => $this->monitor->getRunnerStatus(),
                ],
                [
                    'name' => 'Enqueued Tasks',
                    'title' => '',
                    'value' => $this->monitor->getEnqueuedTasks(),
                ],
                [
                    'name' => 'Finished Tasks',
                    'title' => '',
                    'value' => $this->monitor->getFinishedTasks(),
                ],
                [
                    'name' => 'Latest Enqueued',
                    'title' => '',
                    'value' => $this->monitor->getLastEnqueuedAt()
                        ? date('Y-m-d H:i:s', $this->monitor->getLastEnqueuedAt())
                        : '-',
                ],
                [
                    'name' => 'Latest Finished',
                    'title' => '',
                    'value' => $this->monitor->getLastFinishedAt()
                        ? date('Y-m-d H:i:s', $this->monitor->getLastFinishedAt())
                        : '-',
                ],
                [
                    'name' => 'Mem Allocated',
                    'title' => '',
                    'value' => $this->formatMemory($this->monitor->getMemAllocated()),
                ],
                [
                    'name' => 'Mem Usage',
                    'title' => '',
                    'value' => $this->formatMemory($this->monitor->getMemUsage()),
                ],
                [
                    'name' => 'Running Since',
                    'title' => '',
                    'value' => date('Y-m-d H:i:s', $this->monitor->getRunningSince()),
                ],
            ],
            'results' => array_map(function ($result) {
                $task = $result['task'];
                return [
                    'startTime' => date('Y-m-d H:i:s', $result['startTime']),
                    'finishTime' => date('Y-m-d H:i:s', $result['finishTime']),
                    'command' => $task['command'],
                    'workingDirectory' => $task['workingDirectory'],
                    'exitCode' => $result['exitCode'],
                    'output' => $result['output'] ?: var_export($result, true),
                    'outputBrief' => $result['output'] ? $this->truncateOutput($result['output']) : '',
                ];
            }, array_reverse($this->monitor->getRecentTasks())),
        ];
        return $response->withBody(stream_for($this->engine->render('index', $data)));
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @internal
     */
    public function actionFavicon(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $response->withHeader('Content-Type', 'image/png')
            ->withBody(stream_for(base64_decode('iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAeElEQVQ4y61TWw6AIAxrF4+u564fhgShQ0GX8MEebVkDJcEFSVuQxFufA8iGHVAH0A7XjK4WM3LbOwBspTBiHkVgMQpBzDC6p8aK7Lovnha3tAMHkjllAXhAb+R/ciEFKOwjFb8qgKTuYJe6HKCr/Z5n9p0zF9olniImdOsXukmPAAAAAElFTkSuQmCC')));
    }

    /**
     * Formats memory usage to human-readable format.
     * @param $size
     * @return string
     */
    private function formatMemory($size)
    {
        $unit = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * Truncate long outputs.
     * @param string $output
     * @return string
     */
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