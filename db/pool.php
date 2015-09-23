<?php
class pool
{
    const CACHE_TYPE_MEMCACHE = 'memcache';
    const CACHE_TYPE_REDIS = 'redis';
    const CACHE_TYPE_DB = 'db';
    const CACHE_TYPE_FILE = 'file';
    const CACHE_TYPE_APC = 'apc';

    const DEFAULT_PERSISTENT_ID = 'web';

    /**
     * 数据库链接池
     */
    public static function db($config = 'db', $hash = 0, $checkConnection = false)
    {
        static $db;
        $conf = config::$$config;
        $index = abs($hash) % count($conf['dsn']);
//重新初始化数据库连接
        if (!isset($db[$config]) || !is_a($db[$config][$index], 'PDO')) {
            $db[$config][$index] = new cooldb($config, $hash);
            echo 'new --------';
        } elseif ($checkConnection) {
            $serverInfo = $db[$config][$index]->getAttribute(PDO::ATTR_SERVER_INFO);
            if (strpos(strval($serverInfo), 'Uptime') === false) {
                $db[$config][$index] = new cooldb($config, $hash);
            }
        }
        return $db[$config][$index];
    }


    public static function memcache()
    {
        static $memcache;
    }

    /**
     * 统一缓存接口 共提供5种缓存方式 默认使用memcache
     * $type = 'memcache' memcached缓存
     * $type = 'redis' redis缓存，注意这里只作缓存用 redis其他数据类型操作请使用server_redis类
     * $type = 'db' mysql数据库缓存 数据存在ym_cache表中
     * $type = 'file' 文件缓存 数据存在 /trunk/data/cache/目录中
     * $type = 'apc' apc缓存
     *
     * 建议将$type定义成一个常量 如 !defined('CHEXUN_CACHE_TYPE') && define('CHEXUN_CACHE_TYPE', 'memcache');
     * 这样以后需要切换缓存时可以更快速进行切换
     * pool::cache(CHEXUN_CACHE_TYPE);
     *
     * 对外提供的接口
     * 添加单个缓存: pool::cache()->add('username', 'maxincai', 3000);
     * 设置单个缓存: pool::cache()->set('username', 'maxincai', 3000);
     * 获取单个缓存: pool::cache()->get('username');
     * 添加多个缓存: pool::cache()->madd(array('username' => 'maxincai', 'password' => '123456'), 3000);
     * 设置多个缓存: pool::cache()->mset(array('username' => 'maxincai', 'password' => '123456'), 3000);
     * 获取多个缓存: pool::cache()->mget(array('username', 'password'));
     * 判断缓存存在: pool::cache()->exists('username');
     * 删除单个缓存: pool::cache()->delete('username');
     * 清空所有缓存: pool::cache()->flush();  谨慎使用
     *
     * @param string $type 缓存类型
     * @author maxincai
     * @date 2015/04/24
     * @return mixed
     */
    public static function cache($type = self::CACHE_TYPE_MEMCACHE)
    {
        static $cache;
        $type = strtolower($type);
        if (!isset($cache[$type])) {
            switch ($type) {
                case self::CACHE_TYPE_MEMCACHE:
                    $cache[$type] = new Server_Cache_Memcache(self::DEFAULT_PERSISTENT_ID);
                    break;
                case self::CACHE_TYPE_REDIS:
                    $cache[$type] = new Server_Cache_Redis();
                    break;
                case self::CACHE_TYPE_DB:
                    $cache[$type] = new Server_Cache_Db();
                    break;
                case self::CACHE_TYPE_FILE:
                    $cache[$type] = new Server_Cache_File();
                    break;
                case self::CACHE_TYPE_APC:
                    $cache[$type] = new Server_Cache_Apc();
                    break;
            }
        }
        return $cache[$type];
    }
}

/*useage
include 'config.php';
$db = pool::db('db');
$sql  = 'select * from product';
$rows = $db->rows($sql,0,20);
echo '<pre>';
print_r($rows);

 */
include 'config.php';
include 'cooldb.php';
$db = pool::db('db');
$sql = 'select * from product where id = :id and `name`=:name';
//$param['id'] = 4;
$param['name'] = 'ma';
$res = $db->execute($sql,$param);

print_r($res);