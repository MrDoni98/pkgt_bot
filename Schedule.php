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
    const FEEDBACK = 4;

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
		//$html = @file_get_contents("http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти", 0, stream_context_create(['http' => ['timeout' => 1]]));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://pkgt.kz/learner/index_m.php?ng=".$group."&dat=".$date."&sel=Найти");
		curl_setopt($ch, CURLOPT_HEADER, 0);//Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent:" => "Mozilla/5.0 (Windows NT 10.0)", "rv:" => "AppleWebKit/537.36 Chrome/66.0.3359.181 Safari/537.36"]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$result = curl_exec($ch);
		if($result){
			$reader = new \XMLReader();
			$result = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $result);
			$result = preg_replace('/<noscript\b[^>]*>(.*?)<\/noscript>/is', "", $result);
			$result = preg_replace("/<!--[\s\S]*?-->/", '', $result);
			$reader->XML($result);
			while ($reader->read()) {
				if ($reader->nodeType == XMLReader::ELEMENT) {
					if ($reader->name == 'div'){
						$ans = [];
						while ($reader->read()) {
							if ($reader->nodeType == XMLReader::ELEMENT) {
								if ($reader->name == 'tr'){
									$tds = [];
									while($reader->read()){
										if($reader->nodeType == XMLReader::ELEMENT){
											if ($reader->name == 'td'){
												$tds[] = trim($reader->readString());
											}
										}
									}
								}
							}
						}
					}
				}
			}
			$reader->close();
			$arr = array_chunk($tds, 5);
			array_pop($arr);
			$ans = [];
			foreach($arr as $r){
				unset($r[0], $r[2]);
				$r[1] .= ". ";//номер пары
				$r[3] = mb_strtoupper(mb_substr($r[3], 0, 1)).mb_substr($r[3], 1);
//название пары
				if($r[4] != ""){//у физ-ры не указывается кабинет поэтому он может быть null
					$cabinet = intval($r[4]);
					$r[4] = "(".$r[4]." каб. ";//кабинет
					if($cabinet >= 21 and $cabinet <= 47){
						$r[4] = $r[4]."1 корпус)";
					}elseif ($cabinet >= 50 and $cabinet <= 64) {
						$r[4] = $r[4]."2 корпус)";
					}elseif ($cabinet >= 65 and $cabinet <= 80){
						$r[4] = $r[4]."3 корпус)";
					}else{
						$r[4] = $r[4].")";
					}
				}
				$ans[] = implode(' ', $r);
			}
			if(count($ans) == 0){
				$answer = "Расписание на ".$date." мне пока неизвестно...".PHP_EOL;
				$request_params = array(
					'url' => "http://pkgt.kz/learner/index_m.php?ng=".urlencode($group)."&dat=".$date."&sel=Найти",
					'private' => 0,
					'access_token' => ACCESS_TOKEN,
					'v' => '5.80'
				);
				$get_params = http_build_query($request_params);
				$url = json_decode(file_get_contents('https://api.vk.com/method/utils.getShortLink?'. $get_params))->response->short_url;

				$answer .= "\n\nМожешь проверить на сайте: \n".$url;
			}else{
				$answer = "Расписание ".$group." на ".$date.". ".PHP_EOL.PHP_EOL;
				$answer .= implode(PHP_EOL, $ans);
			}
			return $answer;
		}else{
			$answer = 'Проблемы с доступом к сайту';
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
	}

}