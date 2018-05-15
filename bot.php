<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 * Версия: 2.1.0
 */

if (!isset($_REQUEST)) {
    return;
}

date_default_timezone_set('Asia/Almaty');
header("Content-Type text/html; charset=utf-8");
include('simple_html_dom.php');
$config['confirmation_token'] = "5";
$config['secret'] = '';
//Ключ доступа сообщества
$config['token'] = '';
//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));
if(!isset($data->secret) || $data->secret != $config['secret']){
    echo "неудача";
    exit;
}
if($data->type == 'confirmation'){
    echo $config['confirmation_token'];
}else{
    try{
        $bot = new Bot($config['token'], $data);
    }catch (\Error $error){
        exit;
    }
}

class Bot
{
    private $token;
    private $data;
    public $event;
    public $groups = ['Б', 'Э','ВЛ','Л','Т','ПО','СТС','Д','ДМ','ЭРУ','ПВ','МП','ОВ','ЭМЛ','В','ДСП','ОВМ','СГО','СДМ', 'ПМ'];
    public function __construct($token, $data)
    {
        $this->data = $data;
        $this->token = $token;
        $this->event = $data->type;
        $status = json_decode(file_get_contents("https://api.vk.com/method/groups.getOnlineStatus?group_id=128463549&access_token={$token}&v=5.74"));
        if($status->response->status == "none"){
            file_get_contents("https://api.vk.com/method/groups.enableOnline?group_id=128463549&access_token={$token}&v=5.0");
        }
        $this->event();
    }

    /**
     *
     */
    public function event(){
        switch ($this->event){
            case 'message_new':
                echo 'ok';
                //...получаем id его автора
                $user_id = $this->data->object->user_id;
                //затем с помощью users.get получаем данные об авторе
                $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&v=5.0"));
//и извлекаем из ответа его имя
                $user_name = $user_info->response[0]->first_name;
                $message = $this->data->object->body;
                $msg = explode(" ", $message);
                if(in_array($user_id, [244448617, 284995241/*, 391851804*/])){
                    exit;
                    return;
                }
                $request_params = array(
                    'message_ids' => $this->data->object->id,
                    'peer_id' => $user_id,
                    'access_token' => $this->token,
                    'v' => '5.0'
                );
                $get_params = http_build_query($request_params);
                file_get_contents('https://api.vk.com/method/messages.markAsRead?'. $get_params);
                switch (mb_strtolower($msg[0])){
                    case "штампы":
                        $this->sendMessage($user_id, "Штампы: ", "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424");
                        break;
                    case "советик":
                        $advice = html_entity_decode(json_decode(file_get_contents("http://fucking-great-advice.ru/api/random/random_by_tag/%D1%81%D1%82%D1%83%D0%B4%D0%B5%D0%BD%D1%82%D1%83"))->text);
                        $this->sendMessage("Случайный совет: ".$advice, $user_id);
                        break;
                    case "помощь":
                    case "команды":
                        $this->sendMessage($user_id, "Доступные команды:\n".
                            "● помощь - выводит список всех команд\n".
                            //"● совет - случайный совет студенту\n".
                            //"● установить группу <группа> - установит вашу группу по умолчанию, чтобы использовать команду \"расписание\" без указания группы\n".
                            //"● моя группа - покажет вашу группу по умолчанию\n".
                            "● звонки - расписание звонков\n".
                            "● штампы - пришлёт файлы со штампами\n".
                            "● <группа> - покажет расписание на текущую дату\n".
                            "● <группа> <дата в формате год-месяц-число>\n".
                            "● <группа> на <сегодня(завтра)>\n".
                            "● <группа> на <дата в формате год-месяц-число>\n".
                            "● <группа> на <день недели> - покажет расписание на определённый день недели\n\n".
                            "Примеры: \n".
                            $this->groups[array_rand($this->groups)]."-".rand(1, 4).rand(1, 3)." на завтра\n".
                            $this->groups[array_rand($this->groups)]."-".rand(1, 4).rand(1, 3)." ".date("Y-m-d"));
                        break;
                    case "звонки":
                        $this->sendMessage($user_id,"РАСПИСАНИЕ ЗВОНКОВ:\n".
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
                            "7. 18.50 – 20.20 | 10");
                        break;
                    default:
                        if($this->isValidGroup($msg[0])){
                            if(isset($msg[1])){
                                if($msg[1] == "на" || $msg[1] == "в"){
                                    if(isset($msg[2])){
                                        $string = $msg;
                                        unset($string[0], $string[1]);
                                        if (($date = $this->getDate(implode(" ", $string))) !== false) {
                                            $this->sendMessage($user_id, $this->getSchedule($msg[0], $date));
                                        }else{
                                            $this->sendMessage($user_id, "Извините я вас не понял...\n Доступные команы можно узнать написав мне: \"помощь\"");
                                        }
                                    }else{
                                        $this->sendMessage($user_id, "Пожалуйства укажите на какой день вам нужно узнать расписание занятий");
                                        return;
                                    }
                                }else{
                                    if (($date = $this->getDate($msg[1]))) {
                                        $this->sendMessage($user_id, $this->getSchedule($msg[0], $date));
                                    }else{
                                        $this->sendMessage($user_id, "Извините я вас не понял...\n Доступные команы можно узнать написав мне: \"помощь\"");
                                    }
                                }
                            }else{
                                $this->sendMessage($user_id, $this->getSchedule($msg[0], date("Y-m-d")));
                            }
                        }else{
                            $this->sendMessage($user_id, "Извините я вас не понял...\nДоступные команды можно узнать написав мне: помощь");
                        }
                        break;
                }
                break;
            case "group_join":
                echo "ok";
                $user_id = $this->data->object->user_id;
                //затем с помощью users.get получаем данные об авторе
                $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&v=5.0"));
//и извлекаем из ответа его имя
                $user_name = $user_info->response[0]->first_name;
                $this->sendMessage("Здравствуйте, ".$user_name."! \nСпасибо за подписку я Вас не подведу!\n".
                    "Доступные команды можно узнать написав мне: помощь", $user_id);
                break;
            default:
                echo 'ok';
                break;
        }
    }
    public function isValidGroup(String $group){
        $group = mb_strtoupper($group);
        if(isset($group) && preg_match("/[А-Я]{1,3}\-[1-4][1-4]/", $group)){
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

    }

    public function getSchedule(string $group, String $date){
        $group = mb_strtoupper($group);
        //return "Бот временно не работает. Расписание можете узнать перейдя по ссылке:\n"."http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти";
        $html = file_get_html("http://pkgt.kz/index.php?pgn=12&ng=".$group."&dat=".$date."&sel=Найти");
        if($html !== false){
            $trs = $html->find('div[id=contener] tr[!bgcolor]', 0);
            $ans = [];
            foreach($html->find('div[id=contener] tr[!bgcolor]') as $tr){
                $tds = [];
                foreach($tr->find('td') as $td){
                    $tds[] = $td->plaintext;
                }
                unset($tds[0], $tds[2]);
                $tds[1] .= ".";
                $tds[3] .= ".";
                if($tds[4] != ""){
                    $tds[4] = "(".$tds[4]." каб.)";
                }
                $ans[] = implode(" ", $tds);
            }
            $answer = "Расписание ".$group." на ".$date.PHP_EOL;
            $answer .= implode(PHP_EOL, $ans);
            $html->clear();
            if($answer == "0"){
                $answer = "Расписание на этот день мне пока неизвестно...";
            }
            $request_params = array(
                'url' => "http://pkgt.kz/index.php?pgn=12".urlencode("&ng=".$group."&dat=".$date."&sel=Найти"),
                'private' => 0,
                'access_token' => $this->token,
                'v' => '5.7.3'
            );
            //$get_params = http_build_query($request_params);
            //$url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;
            //$answer .= "\nМожешь проверить на сайте: \n".$url;
            return $answer;
        }else{
            return "Проблемы с доступом к сайту...";
        }
    }

    /**
     * @param string $message
     * @param string $attachment
     */
    public function sendMessage($user_id, string $message = "", string $attachment = ""){
        $request_params = array(
            'message' => $message,
            'user_id' => $user_id,
            'access_token' => $this->token,
            'attachment' => $attachment,
            'v' => '5.0'
        );

        $get_params = http_build_query($request_params);

        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
    }
}
?>
