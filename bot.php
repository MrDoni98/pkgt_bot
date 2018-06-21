<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 * Версия: 3.1.0
 */

if (!isset($_REQUEST)) {
    return;
}
set_time_limit(0);
date_default_timezone_set('Asia/Almaty');
header("Content-Type text/html; charset=utf-8");
include('simple_html_dom.php');
//Инициализация конфигурации
if(!is_file("config.json")){
    file_put_contents("config.json", json_encode([
        'confirmation_token' => '',
        'secret' => '',
        'token' => '',
        'groups' => ["Б", "Э","ВЛ","Л","Т","ПО","СТС","Д","ДМ","ЭРУ","ПВ","МП","ОВ","ЭМЛ","В","ДСП","ОВМ","СГО","СДМ", "ПМ"],
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
try {
    /*
    if (!is_file("pkgt.db")) {
        $db = new \SQLite3("pkgt.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    } else {
        $db = new \SQLite3("pkgt.db", SQLITE3_OPEN_READWRITE);
    }
*/
    $db = new \mysqli($config->host, $config->username, $config->passwd, $config->dbname, $config->port);
    $db->set_charset("utf8");
    $db->query("CREATE TABLE IF NOT EXISTS `users` (`id` INTEGER PRIMARY KEY NOT NULL, `group` TEXT, `window` INT(1));");
    //$db->exec('PRAGMA journal_mode=WAL;');
} catch (\Exception $e) {
    echo($e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    exit;
}
//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));
//Удостоверяемся что запрос пришёл именно с вк
if(!isset($data->secret) || $data->secret != $config->secret){
    echo "неудача";
    exit;
}
if($data->type == 'confirmation'){
    echo $config->confirmation_token;
}else{
    try{
        $bot = new Bot($config, $data, $db);
    } catch (\Error $error){
        echo $error->getMessage(). 'in' . $error->getLine();
    }
}

class Bot
{
    private $token;
    private $data;
    private $APIversion = "5.80";
    /** @var  bool */
    private $debug = false;
    public $user_id;
    public $user_info;
    public $event;
    /** @var  \mysqli */
    private $db;
    //public $groups = ['Б', 'Э','ВЛ','Л','Т','ПО','СТС','Д','ДМ','ЭРУ','ПВ','МП','ОВ','ЭМЛ','В','ДСП','ОВМ','СГО','СДМ', 'ПМ'];
    public $groups = [];

    const MAIN = 0;
    const SCHEDULE = 1;
    const SCHEDULE_DATE = 2;
    const SCHEDULE_GROUP = 3;

    public function __construct($config, $data, $db)
    {
        $this->data = $data;
        $this->token = $config->token;
        $this->event = $data->type;
        $this->db = $db;
        $this->groups = $config->groups;
        if(isset($this->data->object->user_id)){
            $this->user_id = $this->data->object->user_id;
        }else{
            $this->user_id = $this->data->object->from_id;
        }

        //затем с помощью users.get получаем данные об авторе
        $this->user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$this->user_id}&access_token={$this->token}&v={$this->APIversion}"));
        $result = $this->db->query("SELECT * FROM `users` WHERE id = '".$this->user_id."'");
        if($result instanceof \mysqli_result){
            if(!(mysqli_num_rows($result) > 0)){
                $this->db->query("INSERT INTO users (`id`, `group`, `window`) VALUES ('".$this->user_id."', '', 0);");
            }
        }
        $result->close();
        $this->event();
    }

    public function event(){
        switch ($this->event){
            case 'message_new':
                echo 'ok';
                //...получаем id его автора
                $user_id = $this->user_id;
                //затем с помощью users.get получаем данные об авторе
                //$user_info = $this->user_info;
//и извлекаем из ответа его имя
                //$user_name = $user_info->response[0]->first_name;
                //помечаем сообщение как прочитанное
                $request_params = array(
                    'message_ids' => $this->data->object->id,
                    'peer_id' => $user_id,
                    'access_token' => $this->token,
                    'v' => $this->APIversion
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.markAsRead?'. $get_params);
                $status = json_decode(file_get_contents("https://api.vk.com/method/groups.getOnlineStatus?group_id=128463549&access_token={$this->token}&v={$this->APIversion}"));
                if($status->response->status != "online"){
                    json_decode(file_get_contents("https://api.vk.com/method/groups.enableOnline?group_id=128463549&access_token={$this->token}&v={$this->APIversion}"));
                }

                if(isset($this->data->object->body)){
                    $message = $this->data->object->body;
                }else{
                    $message = $this->data->object->text;
                }

                switch ($this->getWindow($user_id)){
                    case self::MAIN:
                        /*
                         * 1 - Расписание
                         * 2 - Звонки
                         * 3 - Штампы
                         * 4 - Миссия колледжа
                         * 5 - Видение колледжа
                         */
                        switch ($message){
                            case "1":
                                if(!is_null($group = $this->getGroup($user_id))){//если пользователь установил группу
                                    $this->setWindow($user_id, self::SCHEDULE);
                                    $this->sendMessage($user_id, str_replace("{group}", $this->getGroup($user_id), $this->getWindowText(self::SCHEDULE)));
                                }else{//иначе просим установить
                                    $this->setWindow($user_id, self::SCHEDULE_GROUP);
                                    $this->sendMessage($user_id, $this->getWindowText(self::SCHEDULE_GROUP));
                                }
                                break;
                            case "2":
                                $this->sendMessage($user_id, "РАСПИСАНИЕ ЗВОНКОВ:\n".
                                    "1. 08.30 – 10.00 | 10\n".
                                    "2. 10.10 – 11.40 | 30\n".
                                    "3. 12.10 – 13.40 | 10\n".
                                    "4. 13.50 – 15.20 | 10\n".
                                    "5. 15.30 – 17.00 | 10\n".
                                    "6. 17.20 – 18.50 | 20\n".
                                    "7. 19.00 – 20.30 | 10\n".
                                    "РАСПИСАНИЕ ЗВОНКОВ (СУББОТА)\n".
                                    "1. 08.30 – 10.00 | 10\n".
                                    "2. 10.10 – 11.40 | 10\n".
                                    "3. 11.50 – 13.20 | 20\n".
                                    "4. 13.40 – 15.10 | 10\n".
                                    "5. 15.20 – 16.50 | 10\n".
                                    "6. 17.00 – 18.30 | 20\n".
                                    "7. 18.50 – 20.20 | 10\n\n".
                                    $this->getWindowText(self::MAIN)
                                );
                                break;
                            case "3":
                                $this->sendMessage($user_id,
                                    "Штампы: ",
                                    "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424"
                                );
                                $this->sendMessage($user_id, $this->getWindowText(self::MAIN));
                                break;
                            case "4":
                                $this->sendMessage($user_id,
                                    "Миссия колледжа: \n".
                                    "Подготовка специалистов, обладающих профессиональными компетенциями, отвечающих современным требованиям рынка труда, \n".
                                    "готовых к непрерывному росту, социальной и профессиональной мобильности, обладающих высокими духовно-нравственными качествами.\n\n".
                                    $this->getWindowText(self::MAIN)
                                );
                                break;
                            case "5":
                                $this->sendMessage($user_id,
                                    "Видение колледжа: \n".
                                    "Сохраняя традиции и внедряя инновации, колледж будет являться гарантом качественного образования, \n".
                                    "обеспечивающего возможность карьерного роста и достойного положения в обществе\n\n".
                                    $this->getWindowText(self::MAIN)
                                );
                                break;
                            default:
                                $this->sendMessage($user_id, $this->getWindowText(self::MAIN));
                                break;
                        }
                        break;
                    case self::SCHEDULE:
                        /*
                         * 1 - Пары на сегодня
                         * 2 - Пары на завтра
                         * 3 - Пары на определённую дату
                         * 4 - Вывести текущую дату
                         * 5 - Сменить группу
                         * 0 - Вернуться в меню
                         */
                        switch ($message){
                            case "0":
                                $this->setWindow($user_id, self::MAIN);
                                $this->sendMessage($user_id, $this->getWindowText(self::MAIN));
                                break;
                            case "1":
                                $group = $this->getGroup($user_id);
                                $date = new DateTime("now");
                                $this->sendMessage($user_id,
                                    $this->getSchedule($group, $date->format("Y-m-d"))."\n\n".
                                    str_replace("{group}", $group, $this->getWindowText(self::SCHEDULE))
                                );
                                break;
                            case "2":
                                $group = $this->getGroup($user_id);
                                $date = new DateTime("now");
                                $date->modify('+1 day');
                                $this->sendMessage($user_id,
                                    $this->getSchedule($group, $date->format("Y-m-d"))."\n\n".
                                    str_replace("{group}", $group, $this->getWindowText(self::SCHEDULE))
                                );
                                break;
                            case "3":
                                $this->setWindow($user_id, self::SCHEDULE_DATE);
                                $this->sendMessage($user_id, $this->getWindowText(self::SCHEDULE_DATE));
                                break;
                            case "4":
                                $group = $this->getGroup($user_id);
                                $this->sendMessage($user_id,
                                    "Сегодня ".date("Y-m-d")."\n\n".
                                    str_replace("{group}", $group, $this->getWindowText(self::SCHEDULE))
                                );
                                break;
                            case "5":
                                $this->setWindow($user_id, self::SCHEDULE_GROUP);
                                $this->sendMessage($user_id, $this->getWindowText(self::SCHEDULE_GROUP));
                                break;
                            default:
                                $this->sendMessage($user_id, str_replace("{group}", $this->getGroup($user_id), $this->getWindowText(self::SCHEDULE)));
                                break;
                        }
                        break;
                    case self::SCHEDULE_DATE://расписание по дате
                        if($message === "0"){
                            $this->setWindow($user_id, self::MAIN);
                            $this->sendMessage($user_id, $this->getWindowText(self::MAIN));
                        }elseif(($d = date_create_from_format("Y-m-d", $message))){//Если сторока является датой в формате Y-m-d
                            $date = date_format($d, 'Y-m-d');
                            $group = $this->getGroup($user_id);
                            $this->setWindow($user_id, self::SCHEDULE);
                            $this->sendMessage($user_id,
                                $this->getSchedule($group, $date)."\n\n".
                                str_replace("{group}", $group, $this->getWindowText(self::SCHEDULE))
                            );
                            return;
                        }else{
                            $this->sendMessage($user_id, str_replace("{group}", $this->getGroup($user_id), $this->getWindowText(self::SCHEDULE_DATE)));
                        }
                        break;
                    case self::SCHEDULE_GROUP:
                        if($message === "0"){
                            $this->setWindow($user_id, self::MAIN);
                            $this->sendMessage($user_id, $this->getWindowText(self::MAIN));
                            return;
                        }
                        if ($this->isValidGroup($message)){
                            $this->setGroup($user_id, $message);
                            $this->setWindow($user_id, self::SCHEDULE);
                            $this->sendMessage($user_id, str_replace("{group}", $this->getGroup($user_id), $this->getWindowText(self::SCHEDULE)));
                        }else{
                            $this->sendMessage($user_id,
                                "> Неверно указана группа\n\n".
                                $this->getWindowText(self::SCHEDULE_GROUP));
                        }
                        break;
                }
                break;
            case "group_join":
                echo "ok";
                $user_id = $this->user_id;
                $user_info = $this->user_info;
                //и извлекаем из ответа его имя
                $user_name = $user_info->response[0]->first_name;
                $this->sendMessage($user_id, "Здравствуйте, ".$user_name."! \nСпасибо за подписку я Вас не подведу!\n");
                break;
            default:
                echo 'ok';
                break;
        }
    }
    public function isValidGroup(String $group){
        $group = mb_strtoupper($group);
        if(preg_match("/[А-Я]{1,4}\-[1-4][1-4]/", $group)){
            $g = explode("-", $group);
            if(in_array(mb_strtoupper($g[0]), $this->groups)){
                return true;
            }
        }
        return false;
    }

    public function getDate(String $string){
        switch(mb_strtolower($string)) {
            case "сегодня":
                $date = new DateTime("now");
                return $date->format("Y-m-d");
                break;
            case "завтра":
                $date = new DateTime("now");
                $date->modify('+1 day');
                return $date->format("Y-m-d");
                break;
            case "вчера":
                $date = new DateTime("now");
                $date->modify('-1 day');
                return $date->format("Y-m-d");
                break;
            default:
                $d = ["воскресенье", "понедельник", "вторник", "среду", "четверг", "пятницу", "субботу"];
                $string = str_ireplace(["среда", "пятница", "суббота"], ["среду", "пятницу", "субботу"], $string);
                if(($ind = array_search(mb_strtolower($string), $d)) !== false){
                    $w = date("w");
                    if($ind <= $w){
                        $date = new DateTime("now");
                        $days = (count($d)- $w) + $ind;
                        $date->modify("+".$days." day");
                        return $date->format("Y-m-d");
                    }elseif($ind > $w){
                        $date = new DateTime("now");
                        $date->modify("+".$w - $ind." day");
                        return $date->format("Y-m-d");
                    }
                }else{
                    if(($d = date_create_from_format("Y-m-d", $string))){
                        return date_format($d, 'Y-m-d');
                    }
                    return " ";
                }
        }
        return " ";
    }

    /**
     * Парсит сайт с расписанием и возфращает текст
     *
     * @param string $group
     * @param String $date
     * @return string
     */
    public function getSchedule(string $group, String $date): string {
        $group = mb_strtoupper($group);
        //return "Бот временно не работает. Расписание можете узнать перейдя по ссылке:\n"."http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти";
        //$html = str_get_html(@file_get_contents("http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти", 0, stream_context_create(['http' => ['timeout' => 1]])));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти");
        //curl_setopt($ch, CURLOPT_PROXY, "5.167.161.105	:53281");
        //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);//Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent:" => "Mozilla/5.0 (Windows NT 10.0)", "rv:" => "AppleWebKit/537.36 Chrome/66.0.3359.181 Safari/537.36"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        if($result){
            $html = str_get_html($result);
        }else{
            //$m = curl_error($ch)."\n";
            //$m .= curl_errno($ch);
            return 'Проблемы с доступом к сайту';
        }
        //curl_close($ch);
        if($html !== false){
            $ans = [];//массив с расписанием построчно
            foreach($html->find('div[id=contener] tr[!bgcolor]') as $tr){
                $tds = [];
                /** @var  $tr  simple_html_dom*/
                foreach($tr->find('td') as $td){
                    $tds[] = $td->plaintext;
                }
                unset($tds[0], $tds[2]);//удаляем из таблицы дату и группу, их мы укажем отдельно
                $tds[1] .= ")";//номер пары
                $tds[3] .= ".";//название пары
                if($tds[4] != ""){//у физ-ры не указывается кабинет поэтому он может быть null
                    $tds[4] = "(".$tds[4]." каб.)";//кабинет
                }
                $ans[] = implode(" ", $tds);//Объеденеям в одну строку
            }
            if(count($ans) == 0){
                $answer = "Расписание на ".$date." мне пока неизвестно...";
            }else{
                $answer = "Расписание ".$group." на ".$date." ".PHP_EOL;
                $answer .= implode(PHP_EOL, $ans);
            }
            $request_params = array(
                'url' => "http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти",
                'private' => 0,
                'access_token' => $this->token,
                'v' => $this->APIversion
            );
            $get_params = http_build_query($request_params);
            $url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;
            $answer .= "\nМожешь проверить на сайте: \n".$url;
            return $answer;
        }
        $answer = "Проблемы с доступом к сайту...";
        $request_params = array(
            'url' => "http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти",
            'private' => 0,
            'access_token' => $this->token,
            'v' => $this->APIversion
        );
        $get_params = http_build_query($request_params);
        $url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;
        $answer .= "\nПопробуй глянуть сам: \n".$url;
        return $answer;
    }

    public function getGroup($user_id){
        $result = $this->db->query("SELECT * FROM `users` WHERE id = '".$user_id."';");
        if($result){
            if(!empty(($group = $result->fetch_assoc()["group"]))){
                $result->free();
                return $group;
            }
        }
        return null;
    }

    /**
     * @param $user_id
     * @param string $group
     */
    public function setGroup($user_id, string $group){
        /*
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO users ('id', 'group', 'window') VALUES (:user_id, :group, :window);");
        $stmt->bindValue(':user_id', (int)$user_id);
        $stmt->bindValue(':group', $group);
        $stmt->bindValue(':window', $this->getWindow($user_id));
        $stmt->execute();
        */
        $this->db->query("UPDATE `users` SET `group` = '".$group."' WHERE id = '".$user_id."';");
    }


    /**
     * @param $user_id
     * @return int
     */
    public function getWindow($user_id): int{
        $result = $this->db->query("SELECT * FROM `users` WHERE id = '".$user_id."'");
        if($result){
            if(!empty(($window = $result->fetch_assoc()["window"]))){
                $result->free();
                return $window;
            }else{
                $this->setWindow($user_id, 0);
                $result->free();
                return 0;
            }
        }else{
            return 0;
        }
    }

    /**
     * @param $user_id
     * @param int $window
     */
    private function setWindow($user_id, int $window){
        /*
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO users ('id', 'window') VALUES (:user_id, :window);");
        $stmt->bindValue(':user_id', (int)$user_id);
        $stmt->bindValue(':group', $this->getGroup($user_id));
        $stmt->bindValue(':window', (int)$window);
        $stmt->execute();
        */
        $this->db->query("UPDATE users SET `window` = '".$window."' WHERE id = '".$user_id."';");
    }

    public function getWindowText(int $window): string {
        $result = "";
        switch ($window){
            case self::MAIN:
                $result = "1 - Расписание занятий\n".
                    "2 - Расписание звонков\n".
                    "3 - Штампы колледжа\n".
                    "4 - Миссия колледжа\n".
                    "5 - Видение колледжа\n\n".

                    "Сайт колледжа: http://pkgt.kz";
                break;
            case self::SCHEDULE:
                $result = "Текущая группа - {group} \n".
                    "1 - Пары на сегодня \n".
                    "2 - Пары на завтра\n".
                    "3 - Пары на определённую дату\n".
                    "4 - Вывести текущую дату\n".
                    "5 - Сменить группу\n\n".

                    "0 - Вернуться в главное меню";
                break;
            case self::SCHEDULE_DATE:
                $result = "Укажите дату в формате год-месяц-число\n".
                    "Например, ".date("Y-m-d")."\n\n".
                    "0 - Вернуться в главное меню";
                break;
            case self::SCHEDULE_GROUP:
                $result = "Укажите группу. Например, ".$this->groups[array_rand($this->groups)]."-".rand(1, 4).rand(1, 3)."\n".
                    "Я знаю группы: ".implode(", ", $this->groups)."\n\n".
                    "0 - Вернуться в главное меню";
                break;
        }
        if($this->debug){
            $result .= "\n\n"."[debug] window: ".$this->getWindow($this->user_id)." group: ".$this->getGroup($this->user_id);
        }
        return $result;
    }

    /**
     * @param $user_id
     * @param string $message
     * @param string $attachment
     */
    public function sendMessage($user_id, string $message = "", string $attachment = ""){
        $request_params = array(
            'message' => $message,
            'user_id' => $user_id,
            'access_token' => $this->token,
            'attachment' => $attachment,
            'v' => $this->APIversion
        );

        $get_params = http_build_query($request_params);

        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    }
}