<?php
$input = file_get_contents('php://input');
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,            "http://snif.biz/dirbot/index.php" );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_POST,           1 );
curl_setopt($ch, CURLOPT_POSTFIELDS,     $input ); 
curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain')); 

$result = curl_exec($ch);
curl_close($ch);