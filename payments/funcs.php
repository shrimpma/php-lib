<?php
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