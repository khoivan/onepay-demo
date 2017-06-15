<?php

include './OnePay.php';




$gateway = $_GET['gateway'];
if(empty($gateway)){
	echo "gateway is null"; die;
}



$onepay = new OnePay($gateway);

$amount = 20000;
echo $onepay->getUrlPayment($amount);

// $merchTxnRef = '20170615035936';
// echo $onepay->getResponsePayment($merchTxnRef);


