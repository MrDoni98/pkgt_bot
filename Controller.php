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

    public function getWindowText(int $window): string {
        $result = "";
        switch ($window){
            case Schedule::MAIN:
                $result = "1 - Расписание занятий\n".
                    "2 - Расписание звонков\n".
                    "3 - Штампы колледжа\n".
                    "4 - Миссия колледжа\n".
                    "5 - Видение колледжа\n".
                    "6 - Показать клавиатуру\n\n".

                    "Сайт колледжа: http://pkgt.kz";
                break;
            case Schedule::SCHEDULE:
                $result = "Текущая группа - {group} \n".
                    "1 - Пары на сегодня \n".
                    "2 - Пары на завтра\n".
                    "3 - Пары на определённую дату\n".
                    "4 - Вывести текущую дату\n".
                    "5 - Сменить группу\n\n".

                    "0 - Вернуться в главное меню";
                break;
            case Schedule::SCHEDULE_DATE:
                $result = "Укажите дату в формате год-месяц-число\n".
                    "Например, ".date("Y-m-d")."\n\n".
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
                        [$this->getButton('Пары', 'primary','{"command":"schedule"}')],
                        [$this->getButton('Звонки', 'default','{"command":"calls"}')],
                        [$this->getButton('Штампы', 'default','{"command":"stamps"}')],
                        [$this->getButton('Миссия', 'default', '{"command":"mission"}')],
                        [$this->getButton('Видение', 'default', '{"command":"conducting"}')],
                        [$this->getButton('Спрятать клавиатуру', 'negative', '{"command":"hide_keyboard"}')]
                    ]];
                break;
            case Schedule::SCHEDULE:
                return ['one_time'=> false,
                    'buttons' => [
                        [$this->getButton('Сегодня', 'default', '{"command":"today"}')],
                        [$this->getButton('Завтра', 'default', '{"command":"tomorrow"}')],
                        [$this->getButton('По дате', 'default', '{"command":"by_date"}')],
                        [$this->getButton('Текущая дата', 'default', '{"command":"current_date"}')],
                        [$this->getButton('Сменить группу', 'default', '{"command":"change_group"}')],
                        [$this->getButton('Назад', 'negative', '{"command":"back"}')]
                    ]];
                break;
            case Schedule::SCHEDULE_DATE:
                return ['one_time'=> true,
                    'buttons' => [
                        [$this->getButton('Назад', 'negative', '{"command":"back"}')]
                    ]];
                break;
            case Schedule::SCHEDULE_GROUP:
                return ['one_time'=> true,
                    'buttons' => [
                        [$this->getButton('Назад', 'negative', '{"command":"back"}')]
                    ]];
                break;
            default:
                return ['one_time'=> true,
                    'buttons' => []];
        }
    }

    public function getButton(string $label, string $color, string $payload = ''){
        return [
            'action' => [
                'type' => 'text',
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'label' => $label
            ],
            'color' => $color
        ];
    }

}