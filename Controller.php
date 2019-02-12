<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 */


class Controller
{
    /** @var  \mysqli */
    private static $db;

    public function __construct(\mysqli $db)
    {
        self::$db = $db;
    }

    public function getGroup($user_id){
        $result = self::$db->query("SELECT * FROM `users` WHERE id = '".$user_id."';");
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
        $stmt = self::$db->prepare("INSERT OR REPLACE INTO users ('id', 'group', 'window') VALUES (:user_id, :group, :window);");
        $stmt->bindValue(':user_id', (int)$user_id);
        $stmt->bindValue(':group', $group);
        $stmt->bindValue(':window', $this->getWindow($user_id));
        $stmt->execute();
        */
        self::$db->query("UPDATE `users` SET `group` = '".$group."' WHERE id = '".$user_id."';");
    }


    /**
     * @param $user_id
     * @return int
     */
    public function getWindow($user_id): int{
        $result = self::$db->query("SELECT * FROM `users` WHERE id = '".$user_id."'");
        if($result){
            if(!empty(($window = $result->fetch_assoc()["window"]))){
                $result->free();
                return $window;
            }else{//пользователь не зарегестрирован
                $stmt = self::$db->query("INSERT INTO users (`id`, `window`, `group`, `keyboard`) VALUES (".$user_id.", 0, NULL, 0);");
                //$this->setWindow($user_id, 0);
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
    public function setWindow($user_id, int $window){
        /*
        $stmt = self::$db->prepare("INSERT OR REPLACE INTO users ('id', 'window') VALUES (:user_id, :window);");
        $stmt->bindValue(':user_id', (int)$user_id);
        $stmt->bindValue(':group', $this->getGroup($user_id));
        $stmt->bindValue(':window', (int)$window);
        $stmt->execute();
        */
        self::$db->query("UPDATE users SET `window` = '".$window."' WHERE id = '".$user_id."';");
    }

    public function getWindowText(int $window, bool $keyboard = false): string {
        $result = "";
        switch ($window){
            case Schedule::MAIN:
                if($keyboard){
                    $result = "Главное меню\n\n".

                        "* - Вернуться в текстовый режим";
                }else{
                    $result = "1 - Расписание занятий\n".
                        "2 - Расписание звонков\n".
                        "3 - Штампы колледжа\n".
                        "4 - Миссия колледжа\n".
                        "5 - Видение колледжа\n".
                        "6 - Погода\n\n".
                        "* - Режим клавиатуры\n\n".

                        "Сайт колледжа: http://pkgt.kz";
                }
                break;
            case Schedule::SCHEDULE:
                if($keyboard){
                    $result = "Текущая группа - {group} \n\n".

                        "0 - Вернуться в главное меню";
                }else{
                    $result = "Текущая группа - {group} \n".
                        "1 - Пары на сегодня \n".
                        "2 - Пары на завтра\n".
                        "3 - Пары на определённую дату\n".
                        "4 - Сменить группу\n\n".

                        "0 - Вернуться в главное меню";
                }
                break;
            case Schedule::SCHEDULE_DATE:
                $result = "Укажите дату в формате год-месяц-число\n".
                    "Например, сегодня: ".date("Y-m-d")."\n\n".
                    "0 - Вернуться в главное меню";
                break;
            case Schedule::SCHEDULE_GROUP:
                $result = "Укажите группу. Например, ".Schedule::$groups[array_rand(Schedule::$groups)]."-".rand(1, 4).rand(1, 3)."\n".
                    "Я знаю группы: ".implode(", ", Schedule::$groups)."\n\n".
                    "0 - Вернуться в главное меню";
                break;
        }
        return $result;
    }

    /**
     * @param int $window
     * @return array
     */
    public function getKeyboard(int $window): array{
        switch ($window){
            case Schedule::MAIN:
                return ['one_time'=> false,
                    'buttons' => [
                        [$this->getButton("\xF0\x9F\x93\x96Пары", 'primary', ["command" => "schedule"])],
                        [$this->getButton("\xE2\x8F\xB0Звонки", 'default', ["command" => "calls"]),
                            $this->getButton("\xE2\x9B\x85Погода", 'default',["command" => "weather"]),
                            $this->getButton("\xF0\x9F\x93\x84Штампы", 'default', ["command" => "stamps"])],
                        [$this->getButton("\xF0\x9F\x92\xA1Миссия", 'default', ["command" => "mission"]),
                            $this->getButton("\xF0\x9F\x92\xA5Видение", 'default', ["command" => "conducting"])],
                        [$this->getButton("\xF0\x9F\x93\xB4Текстовый режим", 'negative', ["command" => "hide_keyboard"])]
                    ]];
                break;
            case Schedule::SCHEDULE:
                return ['one_time'=> false,
                    'buttons' => [
                        [$this->getButton("\xF0\x9F\x95\x92Сегодня", 'default', ["command" => "today"]),
                            $this->getButton("\xF0\x9F\x95\x93Завтра", 'default', ["command" => "tomorrow"])],
                        [$this->getButton("\xF0\x9F\x93\x85По дате", 'default', ["command" => "by_date"])],
                        [$this->getButton("\xE2\x9C\x8FСменить группу", 'primary', ["command" => "change_group"])],
                        [$this->getButton("\xF0\x9F\x94\x99Главное меню", 'negative', ["command" => "back"])]
                    ]];
                break;
            case Schedule::SCHEDULE_DATE:
                return ['one_time'=> true,
                    'buttons' => [
                        [$this->getButton("\xF0\x9F\x94\x99Главное меню", 'negative', ["command" => "back"])]
                    ]];
                break;
            case Schedule::SCHEDULE_GROUP:
                //todo: array_chunk(Schedule::$group)
                return ['one_time'=> true,
                    'buttons' => [
                        [$this->getButton("\xF0\x9F\x94\x99Главное меню", 'negative', ["command" => "back"])]
                    ]];
                break;
            default:
                return ['one_time'=> true,
                    'buttons' => []];
        }
    }

    public function getButton(string $label, string $color, $payload = []){
        return [
            'action' => [
                'type' => 'text',
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'label' => $label
            ],
            'color' => $color
        ];
    }

    public function isKeyboardEnabled($user_id): bool{
        $result = self::$db->query("SELECT * FROM `users` WHERE id = '".$user_id."'");
        if($result){
            if(!empty(($enabled = $result->fetch_assoc()['keyboard']))){
                $result->free();
                return (bool) $enabled;
            }else{
                $this->setKeyboardEnabled($user_id, false);
                $result->free();
                return false;
            }
        }else{
            return false;
        }
    }

    public function setKeyboardEnabled($user_id, bool $enabled = true){
        if($enabled){
            self::$db->query("UPDATE users SET `keyboard` = 1 WHERE id = '".$user_id."';");
        }else{
            self::$db->query("UPDATE users SET `keyboard` = '0' WHERE id = '".$user_id."';");
        }
    }

    public function getWeather(): string {
        //todo: добавить смайлики и форматирование текста
        $url = 'http://api.openweathermap.org/data/2.5/weather?units=metric&lang=ru&lat=54.875278&lon=69.162781&appid='.WEATHER_TOKEN;
        if(file_exists("weather.json")){
            $weather = json_decode(file_get_contents('weather.json'), true);
            if((time() - intval($weather['time'])) > (60 * 60)){//обращение к api раз в час
                $weather = json_decode(file_get_contents($url), true);
                $weather['time'] = time();
                file_put_contents("weather.json", json_encode($weather, JSON_UNESCAPED_UNICODE));
            }
        }else{
            $weather = json_decode(file_get_contents($url), true);
            $weather['time'] = time();
            file_put_contents("weather.json", json_encode($weather, JSON_UNESCAPED_UNICODE));
        }
        $description = $weather['weather'][0]['description'];
        return "Погода в Петропавловске: ".mb_strtoupper(mb_substr($description, 0, 1)).mb_substr($description, 1).
            "\nТемпертура: ".$weather['main']['temp']." °C".
            "\nВлажность: ".$weather['main']['humidity']."%".
            "\nДавление: ".round(intval($weather['main']['pressure'])* 0.750062, 3)." мм р.ст.".
            "\nСкорость ветра: ".$weather['wind']['speed']." м/с".
            "\nВосход: ".date("H:m:s",$weather['sys']['sunrise']).
            "\nЗакат: ".date("H:m:s",$weather['sys']['sunset']);
    }

}