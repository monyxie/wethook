<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Wethook</title>
    <style type="text/css">
        table {
            text-align: left;
            border: 1px solid black;
            border-collapse: collapse;
            font-family: monospace;
        }

        th, td {
            border: 1px solid black;
            padding: 0 1em;
        }
    </style>
</head>
<body>
<p>Welcome! This is <a href="https://github.com/monyxie/wethook">wethook</a>, a runner for webhook-triggered tasks.</p>
<table>
    <tbody>
    <?php foreach ($fields as $field): ?>
        <tr>
            <th title="<?= $this->e($field['title']) ?>"><?= $this->e($field['name']) ?></th>
            <td><?= $this->e($field['value']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>Recent tasks</p>
<table>
    <thead>
    <tr>
        <th>Started At</th>
        <th>Finished At</th>
        <th>Command</th>
        <th>Working Directory</th>
        <th>Exit Code</th>
        <th>Output</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $result): ?>
        <tr>
            <td><?= $this->e($result['startTime']) ?></td>
            <td><?= $this->e($result['finishTime']) ?></td>
            <td><?= $this->e($result['command']) ?></td>
            <td><?= $this->e($result['workingDirectory']) ?></td>
            <td><?= $this->e($result['exitCode']) ?></td>
            <td title="<?= $this->e($result['output']) ?>"><?= $this->e(($result['outputBrief'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>

