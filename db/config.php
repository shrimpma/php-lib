<?php

class config
{
    /**
     * 配置参数
     */
    const KEY = "i5%e()|',\"idi&UH";
    private static $_db = null;
    static $db = array(
        'dsn' => array('mysql:host=localhost;port=3306;dbname=test;charset=utf8'),
        'user' => 'root',
        'pass' => '',
        'options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
            PDO::ATTR_TIMEOUT => 8,
        ),
        'prefix' => '',
        'dbname' => 'test',
    );
    static $db_bbs = array(
        'dsn' => array('mysql:host=db.lan;port=3306;dbname=bbs;charset=utf8'),
        'user' => 'web',
        'pass' => 'yimaoqiche',
        'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'),
        'dbname' => 'bbs',
    );

    static $db_club = array(
        'dsn' => array('mysql:host=db.lan;port=3306;dbname=club;charset=utf8'),
        'user' => 'web',
        'pass' => '',
        'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'),
        'dbname' => 'club',
    );

    static $mallDb = array(
        'dsn' => array('mysql:host=db.lan; port=3306; dbname=mall; charset=utf8'),
        'user' => 'web',
        'pass' => '',
        'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"),
        'prefix' => 'mall_',
    );

    public static function getDB()
    {
        if (empty(self::$_db)) {
            try {
                self::$_db = new PDO(self::$db['dsn'][0], self::$db['user'],
                    self::$db['pass'], self::$db['options']);
            } catch (PDOException $e) {
                die('Database error');
            }
        }
        return self::$_db;
    }

    public static $redis = array(
        'host' => 'cache.redis.lan',
        'port' => '6379',
    );

    public static function reConnect()
    {
        self::$_db = null;
        return self::getDB();
    }
}