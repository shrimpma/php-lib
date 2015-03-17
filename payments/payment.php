<?php

/**
 * @class Payment
 * @brief 支付方式 操作类
 */
//支付状态：支付失败
define ( "PAY_FAILED", - 1);
//支付状态：支付超时
define ( "PAY_TIMEOUT", 0);
//支付状态：支付成功
define ( "PAY_SUCCESS", 1);
//支付状态：支付取消
define ( "PAY_CANCEL", 2);
//支付状态：支付错误
define ( "PAY_ERROR", 3);
//支付状态：支付进行
define ( "PAY_PROGRESS", 4);
//支付状态：支付无效
define ( "PAY_INVALID", 5);

class Payment
{
	/**
	 * @brief 创建支付类实例
	 * @param $payment_id int 支付方式ID
	 * @return 返回支付插件类对象
	 */
	public static function createPaymentInstance($payment_id)
	{
		$paymentRow = self::getPaymentById($payment_id);

		if($paymentRow && isset($paymentRow['class_name']) && $paymentRow['class_name'])
		{
			$class_name = $paymentRow['class_name'];
			$classPath  = './pay_'.$class_name.'/'.$class_name.'.php';
			if(file_exists($classPath))
			{
				require_once($classPath);
				return new $class_name($payment_id);
			}
			else
			{
				exit('支付接口类'.$class_name.'没有找到');
			}
		}
		else
		{
			exit('支付方式不存在');
		}
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的详细配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getPaymentById($payment_id,$key = '')
	{
        global $paymentConfig;
		
		$paymentRow = $paymentConfig[$payment_id];

		if($key)
		{
			return isset($paymentRow[$key]) ? $paymentRow[$key] : '';
		}
       
		return $paymentRow;
	}

	/**
	 * @brief 根据支付方式配置编号  获取该插件的配置信息
	 * @param $payment_id int    支付方式ID
	 * @param $key        string 字段
	 * @return 返回支付插件类对象
	 */
	public static function getConfigParam($payment_id,$key = '')
	{
		$payConfig = self::getPaymentById($payment_id,'config_param');
		if($payConfig)
		{
			$payConfig = JSON::decode($payConfig);
			return isset($payConfig[$key]) ? $payConfig[$key] : '';
		}
		return '';
	}

	/**
	 * @brief 获取订单中的支付信息 M:必要信息; R表示店铺; P表示用户;
	 * @param $payment_id int    支付方式ID
	 * @param $type       string 信息获取方式 order:订单支付;recharge:在线充值;
	 * @param $argument   mix    参数
	 * @return array 支付提交信息
	 */
	public static function getPaymentInfo($payment_id,$type,$argument)
	{
		//最终返回值
		$payment = array();

		//初始化配置参数
		$paymentInstance = Payment::createPaymentInstance($payment_id);
		$configParam = $paymentInstance->configParam();
		foreach($configParam as $key => $val)
		{
			$payment[$key] = '';
		}

		//获取公共信息
		$paymentRow = self::getPaymentById($payment_id,'config_param');
		if($paymentRow)
		{
			$paymentRow = json_decode($paymentRow);
			foreach($paymentRow as $key => $item)
			{
				$payment[$key] = $item;
			}
		}

		if($type == 'order')
		{
			$order_id = $argument;

			//获取订单信息
			$orderObj = new IModel('order');
			$orderRow = $orderObj->getObj('id = '.$order_id.' and status = 1');
			if(empty($orderRow))
			{
				IError::show(403,'订单信息不正确，不能进行支付');
			}

			$payment['M_Remark']    = $orderRow['postscript'];
			$payment['M_OrderId']   = $orderRow['id'];
			$payment['M_OrderNO']   = $orderRow['order_no'];
			$payment['M_Amount']    = $orderRow['order_amount'];

			//用户信息
			$payment['P_Mobile']    = $orderRow['mobile'];
			$payment['P_Name']      = $orderRow['accept_name'];
			$payment['P_PostCode']  = $orderRow['postcode'];
			$payment['P_Telephone'] = $orderRow['telphone'];
			$payment['P_Address']   = $orderRow['address'];
		}
		else if($type == 'recharge')
		{
            //判断用户是否登陆 
            //代码略去

			if(!isset($argument['account']) || $argument['account'] <= 0)
			{
				exit('请填入正确的充值金额');
			}

			//$rechargeObj = new IModel('online_recharge');
			$reData      = array(
				'user_id'     => 11,
				'recharge_no' => '123445566',
				'account'     => $argument['account'],
				'time'        => time(),
				'payment_name'=> $argument['paymentName'],
			);
			//$rechargeObj->setData($reData);
			//$r_id = $rechargeObj->add();
            
            $r_id = 1234;

			//充值时用户id跟随交易号一起发送,以"_"分割
			$payment['M_OrderNO'] = 'recharge_'.$reData['recharge_no'];
			$payment['M_OrderId'] = $r_id;
			$payment['M_Amount']  = $reData['account'];
		}

		global $site_config;
		

		//交易信息
		$payment['M_Time']      = time();
		$payment['M_Paymentid'] = $payment_id;

		//店铺信息
		$payment['R_Address']   = isset($site_config['address']) ? $site_config['address'] : '';
		$payment['R_Name']      = isset($site_config['name'])    ? $site_config['name']    : '';
		$payment['R_Mobile']    = isset($site_config['mobile'])  ? $site_config['mobile']  : '';
		$payment['R_Telephone'] = isset($site_config['phone'])   ? $site_config['phone']   : '';

		return $payment;
	}

	//更新在线充值
	public static function updateRecharge($recharge_no)
	{
		//根据订单号  判断是否存在

		//判断订单状态
		//更新订单 
        //记录日志
		return $is_success;
	}
}