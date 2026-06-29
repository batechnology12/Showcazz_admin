<?php
$serviceAccount = json_decode(file_get_contents(__DIR__.'/medical-app.json'), true);
$pk = $serviceAccount['private_key'];
$res = openssl_pkey_get_private($pk);
var_dump($res);
echo openssl_error_string();
