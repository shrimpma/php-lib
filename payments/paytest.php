<?php
include 'funcs.php';
define('BASE_URL',  getHost().'/payments/');
$site_config =  array('name'=>'个人帐户充值');
$paymentConfig = require('config/payment.php');
//支付方式  支持多种支付方式
$payment_id = 6; 

$paymentInstance = Payment::createPaymentInstance($payment_id);
$paymentRow = array('name'=>'支付宝即时到帐');
$reData   = array('account' => 100 , 'paymentName' => $paymentRow['name']);
//数据包装
$param = Payment::getPaymentInfo($payment_id,'recharge',$reData);
$sendData = $paymentInstance->getSendData($param); 
//提交数据
$paymentInstance->doPay($sendData);