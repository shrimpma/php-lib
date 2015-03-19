<?php
	error_reporting(E_ALL);
	ini_set('display_errors','On');
	include 'rsa.php';
	$path = __DIR__.DIRECTORY_SEPARATOR;
	$rsa = new rsa($path);
	//私钥加密，公钥解密
	echo 'source：Testing:Hello World!<br />';
	$pre = $rsa->privEncrypt('Testing:Hello World!');
	echo 'Private Encrypted:' . $pre . '<br />';

	$pud = $rsa->pubDecrypt($pre);
	echo 'Public Decrypted:' . $pud . '<br />';
    echo '++++++++++++<br>';
	//公钥加密，私钥解密
	echo 'source:working in here!<br />';
	$pue = $rsa->pubEncrypt('working in here!');
	echo 'Public Encrypt:' . $pue . '<br />';

	$prd = $rsa->privDecrypt($pue);
	echo 'Private Decrypt:' . $prd;