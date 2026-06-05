<?php
$s = json_decode(file_get_contents('c:/xampp/htdocs/showcazz/Showcazz_admin/medical-app.json'), true);
echo substr($s['private_key'], 0, 100);
