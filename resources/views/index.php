<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
    <title>Wethook</title>
</head>
<body>
<p>Welcome! This is <a href="https://github.com/monyxie/wethook">wethook</a>, a runner for webhook-triggered tasks.</p>
<table style="text-align:left;">
    <tbody>
    <?php foreach ($fields as $field): ?>
    <tr>
        <th title="<?= $this->e($field['title']) ?>"><?= $this->e($field['name']) ?></th>
        <td><?= $this->e($field['value']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>

