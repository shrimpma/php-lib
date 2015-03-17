<?php
function md5Sign($prestr, $key) {
    $prestr = $prestr . $key;
    return md5($prestr);
}

function getHost($protocol = 'http') {
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
    $baseUrl = $protocol . '://' . $host . '/';
    return $baseUrl;
}

function getClientIp() {
    $realip = NULL;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ipArray as $rs) {
            $rs = trim($rs);
            if ($rs != 'unknown') {
                $realip = $rs;
                break;
            }
        }
    } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $realip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $realip = $_SERVER['REMOTE_ADDR'];
    }

    preg_match("/[\d\.]{7,15}/", $realip, $match);
    $realip = !empty($match[0]) ? $match[0] : '0.0.0.0';
    return $realip;
}

function __autoload($class){
  $class = strtolower($class);
  $file = dirname(__FILE__).'/'.$class.'.php';
  if(file_exists($file)){
      require($file);
  }else{
     $file = __DIR__.'/pay_'.strtolower($class).'/'.$class.'.php';
     if(file_exists($file)){
            require($file);         
     }
  }
    
}
define('BASE_URL',  getHost().'/payments/');

$site_config =  array('name'=>'个人帐户充值');
$payment_id = 6; //支付方式 
$paymentConfig = require('config/payment.php');

$paymentInstance = Payment::createPaymentInstance($payment_id);
$paymentRow = array('name'=>'支付宝即时到帐');

$reData   = array('account' => 100 , 'paymentName' => $paymentRow['name'],'seller_email' => 'liudunming@zhagen.com');
//print_r($reData);
$param = Payment::getPaymentInfo($payment_id,'recharge',$reData);

$sendData = $paymentInstance->getSendData($param); 
//print_r($sendData);exit;
//post 提交数据
$paymentInstance->doPay($sendData);