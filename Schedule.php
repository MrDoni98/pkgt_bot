<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 */

class Schedule
{
    //public $groups = ['Б', 'Э','ВЛ','Л','Т','ПО','СТС','Д','ДМ','ЭРУ','ПВ','МП','ОВ','ЭМЛ','В','ДСП','ОВМ','СГО','СДМ', 'ПМ'];
    public static $groups = [];

    const MAIN = 0;
    const SCHEDULE = 1;
    const SCHEDULE_DATE = 2;
    const SCHEDULE_GROUP = 3;
    const WEATHER = 4;

    public function __construct($groups)
    {
        self::$groups = $groups;
    }

    public static function isValidGroup(string $group){
        $group = mb_strtoupper($group);
        if(preg_match("/[А-Я]{1,4}\-[1-4][1-4]/", $group)){
            $g = explode("-", $group);
            if(in_array($g[0], self::$groups)){
                return true;
            }
        }
        return false;
    }

    public function isGroupExists(string $group){
        $group = mb_strtoupper($group);
        if(in_array($group, self::$groups)){
            return true;
        }
    }

    public static function getDate(String $string){
        switch(mb_strtolower($string)) {
            case "сегодня":
                $date = new \DateTime("now");
                return $date->format("Y-m-d");
                break;
            case "завтра":
                $date = new \DateTime("now");
                $date->modify('+1 day');
                return $date->format("Y-m-d");
                break;
            case "вчера":
                $date = new \DateTime("now");
                $date->modify('-1 day');
                return $date->format("Y-m-d");
                break;
            default:
                $d = ["воскресенье", "понедельник", "вторник", "среду", "четверг", "пятницу", "субботу"];
                $string = str_ireplace(["среда", "пятница", "суббота"], ["среду", "пятницу", "субботу"], $string);
                if(($ind = array_search(mb_strtolower($string), $d)) !== false){
                    $w = date("w");
                    if($ind <= $w){
                        $date = new \DateTime("now");
                        $days = (count($d)- $w) + $ind;
                        $date->modify("+".$days." day");
                        return $date->format("Y-m-d");
                    }elseif($ind > $w){
                        $date = new \DateTime("now");
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
    public static function getSchedule(string $group, String $date): string {
        $group = mb_strtoupper($group);
        //return "Бот временно не работает. Расписание можете узнать перейдя по ссылке:\n"."http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти";
        //$html = str_get_html(@file_get_contents("http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти", 0, stream_context_create(['http' => ['timeout' => 1]])));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти");
        curl_setopt($ch, CURLOPT_HEADER, 0);//Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent:" => "Mozilla/5.0 (Windows NT 10.0)", "rv:" => "AppleWebKit/537.36 Chrome/66.0.3359.181 Safari/537.36"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        if($result){
            $html = str_get_html($result);
        }else{
            return 'Проблемы с доступом к сайту';
        }
        if($html !== false){
            $ans = [];//массив с расписанием построчно
            foreach($html->find('div[id=contener] tr[!bgcolor]') as $tr){
                $tds = [];
                foreach($tr->find('td') as $td){
                    $tds[] = $td->plaintext;
                }
                unset($tds[0], $tds[2]);//удаляем из таблицы дату и группу, их мы укажем отдельно
                $tds[1] .= ". ";//номер пары
                $tds[3] = mb_strtoupper(mb_substr($tds[3], 0, 1)).mb_substr($tds[3], 1);
//название пары
                if($tds[4] != ""){//у физ-ры не указывается кабинет поэтому он может быть null
                    $cabinet = intval($tds[4]);
                    $tds[4] = "(".$tds[4]." каб. ";//кабинет
                    if($cabinet >= 21 and $cabinet <= 47){
                        $tds[4] = $tds[4]."1 корпус)";
                    }elseif ($cabinet >= 50 and $cabinet <= 64) {
                        $tds[4] = $tds[4]."2 корпус)";
                    }elseif ($cabinet >= 65 and $cabinet <= 80){
                        $tds[4] = $tds[4]."3 корпус)";
                    }else{
                        $tds[4] = $tds[4].")";
                    }
                }
                $ans[] = implode(" ", $tds);//Объеденеям в одну строку
            }
            if(count($ans) == 0){
                $answer = "Расписание на ".$date." мне пока неизвестно...".PHP_EOL;
            }else{
                $answer = "Расписание ".$group." на ".$date.". ".PHP_EOL.PHP_EOL;
                $answer .= implode(PHP_EOL, $ans);
            }

            $request_params = array(
                'url' => "http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти",
                'private' => 0,
                'access_token' => ACCESS_TOKEN,
                'v' => '5.80'
            );
            $get_params = http_build_query($request_params);
            $url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;

            $answer .= "\n\nМожешь проверить на сайте: \n".$url;
            return $answer;
        }
        $answer = "Проблемы с доступом к сайту...";

        $request_params = array(
            'url' => "http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти",
            'private' => 0,
            'access_token' => ACCESS_TOKEN,
            'v' => '5.80'
        );
        $get_params = http_build_query($request_params);
        $url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;

        $answer .= "\nПопробуй глянуть сам: \n".$url;
        return $answer;
    }

}