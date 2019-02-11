<?php

/**
 * ПКЖТ Бот
 * Разработал MrDoni98(vk.com/mrdoni98) специально для pkgt.kz
 * При поддержке Ethicist(vk.com/ethicist)
 */

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;

class ServerHandler extends VKCallbackApiServerHandler {

    /** @var  Controller */
    public $controller;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    function confirmation(int $group_id, ?string $secret) {
        if ($secret === SECRET && $group_id === GROUP_ID) {
            echo CONFIRMATION_TOKEN;
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object) {
        echo 'ok';
        $user_id = $object['from_id'];
        $peer_id = $object['peer_id'];

        if(in_array($user_id, [244448617, 284995241])){

            //exit;
        }

        $vk = new VKApiClient('5.80');
        $messages = $vk->messages();
        $controller = $this->controller;

        $user_name = $vk->users()->get(ACCESS_TOKEN, [
            'user_ids' => $user_id
        ])[0]['first_name'];

        $status = json_decode(file_get_contents("https://api.vk.com/method/groups.getOnlineStatus?group_id=128463549&access_token=".ACCESS_TOKEN."&v=".API_VERSION.""));
        if($status->response->status != "online"){
            json_decode(file_get_contents("https://api.vk.com/method/groups.enableOnline?group_id=128463549&access_token=".ACCESS_TOKEN."&v=".API_VERSION.""));
        }

        $text = $object['text'];//текст сообщения
        if($peer_id !== $user_id){//если написали в беседе
            $msg = explode(" ", $text);
            $chat_id = $peer_id - 2000000000;
            if(isset($object['action'])){
                $action = $object['action'];
                if($action->type == "chat_invite_user" && $action->member_id == -128463549){
                    $messages->send(ACCESS_TOKEN, [
                        "peer_id" => $peer_id,
                        "chat_id" => $chat_id,
                        "message" => "Здравствуйте! Я неофициальный бот колледжа ПКЖТ.\n".
                            "Для просмотра доступных команд напишите: !помощь"
                    ]);
                }
            }
            if(substr($text, 0, 1) != '/'){
                exit;
            }
            $appeal = "@id".$user_id."(".$user_name."), ";
            switch (mb_strtolower($msg[0])){
                case "/штампы":
                    $this->sendChatMessage($chat_id, $appeal."Штампы: ", "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424");
                    break;
                case "/помощь":
                case "/команды":
                    $this->sendChatMessage($chat_id, $appeal."Доступные команды:\n".
                        "● /помощь - выводит список всех команд\n".
                        "● /звонки - расписание звонков\n".
                        "● /штампы - пришлёт файлы со штампами\n".
                        "● /погода\n".
                        "● /<группа> - покажет расписание на текущую дату\n".
                        "● /<группа> <дата в формате год-месяц-число>\n".
                        "● /<группа> на <сегодня(завтра)>\n".
                        "● /<группа> на <дата в формате год-месяц-число>\n".
                        "● /<группа> на <день недели> - покажет расписание на определённый день недели\n\n".
                        "Примеры: \n".
                        "/".Schedule::$groups[array_rand(Schedule::$groups)]."-".rand(1, 4).rand(1, 3)." на завтра\n".
                        "/".Schedule::$groups[array_rand(Schedule::$groups)]."-".rand(1, 4).rand(1, 3)." ".date("Y-m-d"));
                    break;
                case "/погода":
                    $this->sendChatMessage($chat_id, $appeal.$controller->getWeather());
                    break;
                case "/звонки":
                    $this->sendChatMessage($chat_id,$appeal."РАСПИСАНИЕ ЗВОНКОВ:\n".
                        "1. 08.30 – 10.00 | 10\n".
                        "2. 10.10 – 11.40 | 30\n".
                        "3. 12.10 – 13.40 | 10\n".
                        "4. 13.50 – 15.20 | 10\n".
                        "5. 15.30 – 17.00 | 10\n".
                        "6. 17.20 – 18.50 | 20\n".
                        "7. 19.00 – 20.30\n".
                        "РАСПИСАНИЕ ЗВОНКОВ (СУББОТА)\n".
                        "1. 08.30 – 10.00 | 10\n".
                        "2. 10.10 – 11.40 | 10\n".
                        "3. 11.50 – 13.20 | 20\n".
                        "4. 13.40 – 15.10 | 10\n".
                        "5. 15.20 – 16.50 | 10\n".
                        "6. 17.00 – 18.30 | 20\n".
                        "7. 18.50 – 20.20");
                    break;
                default:
                    $msg[0] = str_replace('/', '', $msg[0]);
                    if(Schedule::isValidGroup($msg[0])){
                        if(isset($msg[1])){
                            if($msg[1] == "на" || $msg[1] == "в"){
                                if(isset($msg[2])){
                                    $string = $msg;
                                    unset($string[0], $string[1]);
                                    if (($date = Schedule::getDate(implode(" ", $string))) !== false) {
                                        $this->sendChatMessage($chat_id, $appeal.Schedule::getSchedule($msg[0], $date));
                                    }else{
                                        $this->sendChatMessage($chat_id, $appeal."Извините я вас не понял...\n Доступные команы можно узнать написав мне: \"помощь\"");
                                    }
                                }else{
                                    $this->sendChatMessage($chat_id, $appeal."Пожалуйства укажите на какой день вам нужно узнать расписание занятий");
                                    return;
                                }
                            }else{
                                if (($date = Schedule::getDate($msg[1]))) {
                                    $this->sendChatMessage($chat_id, $appeal.Schedule::getSchedule($msg[0], $date));
                                }else{
                                    $this->sendChatMessage($chat_id, $appeal."Извините я вас не понял...\n Доступные команы можно узнать написав мне: \"помощь\"");
                                }
                            }
                        }else{
                            $this->sendChatMessage($chat_id, $appeal.Schedule::getSchedule($msg[0], date("Y-m-d")));
                        }
                    }
                    break;
            }
            exit;
        }else{
            //В беседе боту неизвестен айди чата, поэтому сработает только в личных сообщениях
            $messages->markAsRead(ACCESS_TOKEN, [
                'peer_id' => $user_id,
                'start_message_id' => $object['id']
            ]);
        }

        $keyboard = $controller->isKeyboardEnabled($user_id);

        if(isset($object['payload'])){//если пользователь нажал на кнопку
            $payload = json_decode($object['payload'], true);
            switch ($payload['command']?? ''){
                case 'start':
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $controller->setKeyboardEnabled($user_id, true);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    break;
                case 'back':
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    break;
                case 'schedule':
                    if(!is_null($group = $controller->getGroup($user_id))){//если пользователь установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE);
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, true)));
                    }else{//иначе просим установить
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE_GROUP, true)));
                    }
                    break;
                case 'calls':
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
                        "7. 18.50 – 20.20 \n\n".

                        "* - Вернуться в текстовый режим"
                    );
                    break;
                case 'stamps':
                    $this->sendMessage($user_id,
                        "Штампы: \n\n".

                        "* - Вернуться в текстовый режим",
                        "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424"
                    );
                    break;
                case 'mission':
                    $this->sendMessage($user_id,
                        "Миссия колледжа: \n".
                        "Подготовка специалистов, обладающих профессиональными компетенциями, отвечающих современным требованиям рынка труда, \n".
                        "готовых к непрерывному росту, социальной и профессиональной мобильности, обладающих высокими духовно-нравственными качествами.\n\n".

                        "* - Вернуться в текстовый режим"
                    );
                    break;
                case 'conducting':
                    $this->sendMessage($user_id,
                        "Видение колледжа: \n".
                        "Сохраняя традиции и внедряя инновации, колледж будет являться гарантом качественного образования, \n".
                        "обеспечивающего возможность карьерного роста и достойного положения в обществе\n\n".

                        "* - Вернуться в текстовый режим"
                    );
                    break;
                case 'weather':
                    $this->sendMessage($user_id, $controller->getWeather().
                        "\n\n* - Вернуться в текстовый режим");
                    break;
                case 'today':
                    if(is_null($group = $controller->getGroup($user_id))){//если пользователь не установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, true));
                        return;
                    }

                    $group = $controller->getGroup($user_id);
                    $date = new \DateTime("now");
                    $this->sendMessage($user_id, Schedule::getSchedule($group, $date->format("Y-m-d"))."\n");
                    break;
                case 'tomorrow':
                    if(is_null($group = $controller->getGroup($user_id))){//если пользователь не установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, true));
                        return;
                    }

                    $group = $controller->getGroup($user_id);
                    $date = new \DateTime("now");
                    $date->modify('+1 day');
                    $this->sendMessage($user_id, Schedule::getSchedule($group, $date->format("Y-m-d"))."\n");
                    break;
                case 'by_date':
                    if(is_null($group = $controller->getGroup($user_id))){//если пользователь не установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, true));
                        return;
                    }

                    $controller->setWindow($user_id, Schedule::SCHEDULE_DATE);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_DATE, true));
                    break;
                case 'change_group':
                    $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                    /*
                    $this->sendMessage($user_id, "Укажите группу. Например, ".Schedule::$groups[array_rand(Schedule::$groups)]."-".rand(1, 4).rand(1, 3)."\n".
                        "Я знаю группы: ".implode(", ", Schedule::$groups)."\n", '', $controller->getKeyboard(Schedule::SCHEDULE_GROUP));
                    */
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, true));
                    break;
                case 'hide_keyboard':
                    $controller->setKeyboardEnabled($user_id, false);
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                    break;
                default:
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    break;
            }
        }else{//если написал текстом
            switch ($controller->getWindow($user_id)){
                case Schedule::MAIN:
                    /*
                     * 1 - Расписание
                     * 2 - Звонки
                     * 3 - Штампы
                     * 4 - Миссия колледжа
                     * 5 - Видение колледжа
                     * * - Показать клавиатуру клавиатуру
                     */
                    switch ($text){
                        case "1":
                            if(!is_null($group = $controller->getGroup($user_id))){//если пользователь установил группу
                                $controller->setWindow($user_id, Schedule::SCHEDULE);
                                $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE)));
                            }else{//иначе просим установить
                                $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                                $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP));
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
                                "7. 18.50 – 20.20 | 10\n",  '');
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "3":
                            $this->sendMessage($user_id,
                                "Штампы: ",
                                "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "4":
                            $this->sendMessage($user_id,
                                "Миссия колледжа: \n".
                                "Подготовка специалистов, обладающих профессиональными компетенциями, отвечающих современным требованиям рынка труда, \n".
                                "готовых к непрерывному росту, социальной и профессиональной мобильности, обладающих высокими духовно-нравственными качествами.\n");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "5":
                            $this->sendMessage($user_id,
                                "Видение колледжа: \n".
                                "Сохраняя традиции и внедряя инновации, колледж будет являться гарантом качественного образования, \n".
                                "обеспечивающего возможность карьерного роста и достойного положения в обществе");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "6":
                            $this->sendMessage($user_id, $controller->getWeather());
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "*":
                            if($keyboard = $controller->isKeyboardEnabled($user_id)){//Текстовый режим
                                $controller->setKeyboardEnabled($user_id, false);
                            }else{//Клавиатурный режим
                                $controller->setKeyboardEnabled($user_id, true);
                            }
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, !$keyboard));
                            break;
                        default:
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                    }
                    break;
                case Schedule::SCHEDULE:
                    /*
                     * 1 - Пары на сегодня
                     * 2 - Пары на завтра
                     * 3 - Пары на определённую дату
                     * 4 - Вывести текущую дату
                     * 5 - Сменить группу
                     * 0 - Вернуться в меню
                     */
                    switch ($text){
                        case "0":
                            $controller->setWindow($user_id, Schedule::MAIN);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                            break;
                        case "1":
                            $group = $controller->getGroup($user_id);
                            $date = new \DateTime("now");
                            $this->sendMessage($user_id,
                                Schedule::getSchedule($group, $date->format("Y-m-d"))."\n\n".
                                str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE)));
                            break;
                        case "2":
                            $group = $controller->getGroup($user_id);
                            $date = new \DateTime("now");
                            $date->modify('+1 day');
                            $this->sendMessage($user_id,
                                Schedule::getSchedule($group, $date->format("Y-m-d"))."\n\n".
                                str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE)));
                            break;
                        case "3":
                            $controller->setWindow($user_id, Schedule::SCHEDULE_DATE);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_DATE));
                            break;
                        case "4":
                            $group = $controller->getGroup($user_id);
                            $this->sendMessage($user_id,
                                "Сегодня ".date("Y-m-d")."\n\n".
                                str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE)));
                            break;
                        case "5":
                            $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP));
                            break;
                        default:
                            $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE)));
                            break;
                    }
                    break;
                case Schedule::SCHEDULE_DATE://расписание по дате
                    if($text === "0"){
                        $controller->setWindow($user_id, Schedule::MAIN);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                    }elseif(($d = date_create_from_format("Y-m-d", $text))){//Если сторока является датой в формате Y-m-d
                        $date = date_format($d, 'Y-m-d');
                        $group = $controller->getGroup($user_id);
                        $controller->setWindow($user_id, Schedule::SCHEDULE);
                        $this->sendMessage($user_id,
                            Schedule::getSchedule($group, $date)."\n\n".
                            str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE)));
                        return;
                    }else{
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE_DATE)));
                    }
                    break;
                case Schedule::SCHEDULE_GROUP://установка группы
                    if($text === "0"){
                        $controller->setWindow($user_id, Schedule::MAIN);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN));
                        return;
                    }
                    if (Schedule::isValidGroup($text)){
                        $controller->setGroup($user_id, $text);
                        $controller->setWindow($user_id, Schedule::SCHEDULE);
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                    }else{
                        $this->sendMessage($user_id,
                            "> Неверно указана группа\n\n".
                            $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard));
                    }
                    break;
            }
        }
    }

    /**
     * @param $user_id
     * @param string $message
     * @param string $attachment
     */
    public function sendMessage($user_id, string $message = "", string $attachment = ""){
        $vk = new VKApiClient('5.80');
        $messages = $vk->messages();
        $controller = $this->controller;

        $request_params = array(
            'message' => $message,
            'peer_id' => $user_id
        );

        if($this->controller->isKeyboardEnabled($user_id)){
            $request_params['keyboard'] = json_encode($controller->getKeyboard($controller->getWindow($user_id)), JSON_UNESCAPED_UNICODE);
        }else{
            $request_params['keyboard'] = json_encode(['one_time'=> true, 'buttons' => []], JSON_UNESCAPED_UNICODE);
        }

        if($attachment !== ""){
            $request_params['attachment'] = $attachment;
        }

        $messages->send(ACCESS_TOKEN, $request_params);
    }

    public function sendChatMessage($chat_id, string $message = "", string $attachment = ""){
        $vk = new VKApiClient('5.80');
        $messages = $vk->messages();

        $request_params = array(
            'message' => $message,
            'chat_id' => $chat_id
        );

        if($attachment !== ""){
            $request_params['attachment'] = $attachment;
        }

        $messages->send(ACCESS_TOKEN, $request_params);
    }
}