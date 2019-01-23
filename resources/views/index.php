<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Webhooked</title>
</head>
<body>
<p>Welcome! This is <i>webhooked</i>, a task runner for webhook-triggered tasks.</p>
<table>
    <thead>
    <tr>
        <th title="Number of tasks enqueued.">Enqueued</th>
        <th title="Number of tasks finished.">Finished</th>
        <th title="Time at which the latest task was enqueued.">Last Enqueued</th>
        <th title="Time at which the latest task finished running.">Last Finished</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td><?= $numEnqueued ?></td>
        <td><?= $numFinished ?></td>
        <td><?= date('Y-m-d H:i:s', $lastEnqueuedAt) ?></td>
        <td><?= date('Y-m-d H:i:s', $lastFinishedAt) ?></td>
    </tr>
    </tbody>
</table>
</body>
</html>

