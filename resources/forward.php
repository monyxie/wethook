<?php
$forward_url = 'http://localhost:33133/';

if (!function_exists('getallheaders'))  {
    function getallheaders()
    {
        if (!is_array($_SERVER)) {
            return array();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

$post_data   = file_get_contents('php://input');
$headers     = [];
foreach (getallheaders() as $key => $val) {
    $headers []= "$key: $val";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
curl_setopt($ch, CURLOPT_URL, $forward_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);

echo $response;
