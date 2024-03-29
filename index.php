<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 * Версия: 4.6.2
 */

set_time_limit(10);
date_default_timezone_set('Asia/Almaty');
header("Content-Type text/html; charset=utf-8");
define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);

if(!is_file("config.json")){
    file_put_contents("config.json", json_encode([
        'confirmation_token' => '',
        'secret' => '',
        'token' => '',
        'api_version' => '',
        'group_id' => 123,
        'weather_token' => '',
        'groups' => ["Б", "Э","ВЛ","Л","Т","ПО","СТС","Д","ДМ","ЭРУ","ПВ","МП","ОВ","ЭМЛ","В","ДСП","ОВМ","СГО","СДМ", "ПМ", "ЭМО"],
        'mysql' => [
            'host' => '127.0.0.1',
            'username' => 'username',
            'passwd' => '12345',
            'dbname' => 'db',
            'port' => 3306
        ]
    ]));
}
$config = json_decode(file_get_contents("config.json"));

define('ACCESS_TOKEN', $config->token);
define('GROUP_ID', $config->group_id);
define('SECRET', $config->secret);
define('CONFIRMATION_TOKEN', $config->confirmation_token);
define('API_VERSION', $config->api_version);
define('WEATHER_TOKEN', $config->weather_token);
define('OWNER_ID', $config->owner_id);
define('ADMINS_ID', $config->admins_id);



//подключаемся к бд

try {
    $db = new \mysqli($config->host, $config->username, $config->passwd, $config->dbname, $config->port);
    $db->set_charset("utf8");
    $db->query("CREATE TABLE IF NOT EXISTS `users` (`id` INTEGER PRIMARY KEY NOT NULL, `group` TEXT, `window` INT(1), `keyboard` VARCHAR(1));");
    //$db->exec('PRAGMA journal_mode=WAL;');
} catch (\Exception $e) {
    echo($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
	file_put_contents('error.txt', date("Y-m-d H:i:s ").$e->getMessage()."\n".$e->getTraceAsString());
    exit;
}


spl_autoload_register(function ($className){
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    include_once(ROOT . $className . '.php');
});
include_once(ROOT."vendor/autoload.php");

new Schedule($config->groups);
$handler = new ServerHandler($db);
$data = json_decode(file_get_contents('php://input'));
if(empty($data)){
    echo 'упс';
    exit;
}
try{
    $handler->parse($data);
}catch (Exception $e){
    file_put_contents('error.txt', date("Y-m-d H:i:s ").$e->getMessage()."\n".$e->getTraceAsString());
}
