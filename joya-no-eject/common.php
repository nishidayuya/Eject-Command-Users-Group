<?php 
require_once 'HTTP/OAuth/Consumer.php';


/* Twitter Application Information */
$consumer_key    = '';
$consumer_secret = '';
$callback        = 'http://eject.kokuda.org/joya/joya.php';


$consumer = new HTTP_OAuth_Consumer($consumer_key, $consumer_secret);
$http_request = new HTTP_Request2();
$http_request->setConfig('ssl_verify_peer', false);
$consumer_request = new HTTP_OAuth_Consumer_Request;
$consumer_request->accept($http_request);
$consumer->accept($consumer_request);

?>
