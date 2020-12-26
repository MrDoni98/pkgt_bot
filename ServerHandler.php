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

    public function messageNew(int $group_id, ?string $secret, array $obj) {
        echo 'ok';

        $object = json_decode(json_encode($obj["message"]), true);// $obj["message"] является stdClass нам нужен array
        $client_info = json_decode(json_encode($obj["client_info"]), true);

        $user_id = $object['from_id'];
        $peer_id = $object['peer_id'];

        if(in_array($user_id, [244448617, 284995241])){
            // для великих целей xD
            //$this->sendMessage(244448617, "debug");
            //exit;
        }

        $vk = new VKApiClient(API_VERSION);
        $controller = $this->controller;

        $messages = $vk->messages();
        $user = $vk->users()->get(ACCESS_TOKEN, [
            'user_ids' => $user_id
        ]);
        $user_name = array_shift($user)['first_name'];

        /*
        $status = json_decode(file_get_contents("https://api.vk.com/method/groups.getOnlineStatus?group_id=128463549&access_token=".ACCESS_TOKEN."&v=".API_VERSION.""));
        if($status->response->status != "online"){
            json_decode(file_get_contents("https://api.vk.com/method/groups.enableOnline?group_id=128463549&access_token=".ACCESS_TOKEN."&v=".API_VERSION.""));
        }*/

        $text = $object['text'];//текст сообщения
        $text = preg_replace("<^\[club128463549\|.*\][\s|]>", "", $text);//срезаем обращение

        if($peer_id !== $user_id){//если написали в беседе
            $msg = explode(" ", $text);
            //$chat_id = $peer_id - 2000000000;

            if(isset($object['action'])){
                if($object['action']['type'] == "chat_invite_user" && $object['action']['member_id'] == -128463549){
					$this->sendMessage($peer_id, "Здравствуйте! Я неофициальный бот колледжа ПКЖТ.\n".
						"Для просмотра доступных команд напишите: /помощь");
                }
            }
            if(substr($text, 0, 1) != '/'){
                exit;
            }

            $appeal = "@id".$user_id."(".$user_name."), ";
            switch (mb_strtolower($msg[0])){
                case "/штампы":
                    $this->sendMessage($peer_id, $appeal."Штампы: ", "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424");
                    break;
                case "/помощь":
                case "/команды":
                    $this->sendMessage($peer_id, $appeal."Доступные команды:\n".
                        "● /помощь - выводит список всех команд\n".
                        "● /звонки - расписание звонков\n".
                        "● /штампы - пришлёт файлы со штампами\n".
						"● /кнопки <группа> - добавить кнопки для быстрого вывова расписания группы\n".
						"● /кнопки убрать - убрать кнопки быстрого вывода расписания\n".
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
                    $this->sendMessage($peer_id, $appeal.$controller->getWeather());
                    break;
                case "/звонки":
                    $this->sendMessage($peer_id,$appeal."РАСПИСАНИЕ ЗВОНКОВ:\n".
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

                    if($msg[0] == "кнопки"){
                        if(!isset($msg[1])){
                            $this->sendMessage($peer_id, "Укажите группу");
                            return;
                        }
                        if($msg[1] == "убрать"){
							$request_params = array(
								'message' => "Кнопки убраны",
								'peer_id' => $peer_id,
								'random_id' => time(),
								'keyboard' => json_encode(['one_time'=> true, 'buttons' => []], JSON_UNESCAPED_UNICODE)
							);
							$messages->send(ACCESS_TOKEN, $request_params);
                            return;
                        }
                        if(!Schedule::isValidGroup($msg[1])){
                            $this->sendMessage($peer_id, "Неверно указана группа!");
                            return;
                        }
						$request_params = array(
							'message' => "Добавлены кнопки для быстрого доступа к расписанию группы ".$msg[1]."\nЧтобы убрать кнопки введите: /кнопки убрать",
							'peer_id' => $peer_id,
							'random_id' => time(),
							'keyboard' => json_encode(['one_time'=> false,
								'buttons' => [
									[$controller->getButton("/".$msg[1], 'default', ["/".$msg[1]]), $controller->getButton("/".$msg[1]." на завтра", 'default', ["/".$msg[1]." на завтра"])]
								]], JSON_UNESCAPED_UNICODE)
						);
						$messages->send(ACCESS_TOKEN, $request_params);
                        return;
                    }
                    if(Schedule::isValidGroup($msg[0])){
                        if(isset($msg[1])){
                            if($msg[1] == "на" || $msg[1] == "в"){
                                if(isset($msg[2])){
                                    $string = $msg;
                                    unset($string[0], $string[1]);
                                    if (($date = Schedule::getDate(implode(" ", $string))) !== false) {
                                        $this->sendMessage($peer_id, $appeal.Schedule::getSchedule($msg[0], $date));
                                    }else{
                                        $this->sendMessage($peer_id, $appeal."Извините я вас не понял...\n Доступные команды можно узнать написав мне: \"помощь\"");
                                    }
                                }else{
                                    $this->sendMessage($peer_id, $appeal."Пожалуйства укажите на какой день вам нужно узнать расписание занятий");
                                    return;
                                }
                            }else{
                                if (($date = Schedule::getDate($msg[1]))) {
                                    $this->sendMessage($peer_id, $appeal.Schedule::getSchedule($msg[0], $date));
                                }else{
                                    $this->sendMessage($peer_id, $appeal."Извините я вас не понял...\n Доступные команды можно узнать написав мне: \"помощь\"");
                                }
                            }
                        }else{
                            $this->sendMessage($peer_id, $appeal.Schedule::getSchedule($msg[0], date("Y-m-d")));
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
        if(isset($client_info["keyboard"]) and !$client_info["keyboard"]){
            $controller->setKeyboardEnabled($user_id, false);
            $keyboard = false;
        }

        if(isset($object['payload'])){//если пользователь нажал на кнопку
            $payload = json_decode($object['payload'], true);
            switch ($payload['command']?? ''){
                case 'start':
                    $controller->setWindow($user_id, Schedule::MAIN);
					if(isset($client_info["keyboard"]) and !$client_info["keyboard"]){
						$keyboard = false;
					}else{
						$keyboard = true;
					}
                    $controller->setKeyboardEnabled($user_id, $keyboard);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    break;
                case 'back':
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    break;
                case 'schedule':
                    if(!is_null($group = $controller->getGroup($user_id))){//если пользователь установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE);
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                    }else{//иначе просим установить
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard)));
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
                        "7. 19.00 – 20.30 \n".
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
				case 'tomorrow':
                    if(is_null($group = $controller->getGroup($user_id))){//если пользователь не установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard));
                        return;
                    }

                    $group = $controller->getGroup($user_id);
                    $date = new \DateTime("now");
                    $day = 'Сегодня';
                    if($payload['command'] == 'tomorrow'){
						$date->modify('+1 day');
						$day = 'Завтра';
					}
                    if($date->format('N') == 7){//если воскресенье, смотрим на понедельник
						$date->modify('+1 day');
						$day = $day . " воскресенье, смотрю расписание на понедельник: \n";
					}else{
                    	$day = '';
					}
                    $this->sendMessage($user_id, $day. Schedule::getSchedule($group, $date->format("Y-m-d"))."\n");
                    $this->sendMessage($user_id, str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                    break;
                case 'by_date':
                    if(is_null($group = $controller->getGroup($user_id))){//если пользователь не установил группу
                        $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard));
                        return;
                    }

                    $controller->setWindow($user_id, Schedule::SCHEDULE_DATE);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_DATE, $keyboard));
                    break;
                case 'change_group':
                    $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                    if(!isset($payload['group'])){
                        if(isset($payload['page'])){
                            $page = (int) $payload['page'];
                        }else{
                            $page = 0;
                        }
                        $key_brd = ['one_time'=> false, 'buttons' => []];
                        $groups = array_chunk(Schedule::$groups, 3);
                        $page_count = ceil(count($groups)/4);
                        if($page >= $page_count){
                            $this->sendMessage($user_id, "Страица не найдена");//если вдруг нагрузку подменят
                            return;
                        }
                        $test = array_slice($groups, $page*4, 4);
                        foreach ($test as $lines){
                            $buttons = [];
                            foreach ($lines as $grp){
                                $buttons[] = $controller->getButton($grp, 'default', ['command' => 'change_group', 'group' => $grp]);
                            }
                            $key_brd['buttons'][] = $buttons;
                        }
                        $swing = [];
                        if ($page > 0){
                            $swing[] = $controller->getButton("\xE2\x8F\xAAНазад", 'primary', ['command' => 'change_group', 'page' => $page - 1]);//next
                        }
                        if(($page+1) < $page_count){
                            $swing[] = $controller->getButton("\xE2\x8F\xA9Далее", 'primary', ['command' => 'change_group', 'page' => $page + 1]);//prevision
                        }
                        $key_brd['buttons'][] = $swing;
                        $key_brd['buttons'][] = [$controller->getButton("\xF0\x9F\x94\x99Главное меню", 'negative', ["command" => "back"])];
						$request_params = array(
							'message' => "[".($page+1)."/".$page_count."] Выберите группу: \n\n0 - Вернуться в главное меню",
							'peer_id' => $user_id,
							'random_id' => time(),
							'keyboard' => json_encode($key_brd, JSON_UNESCAPED_UNICODE)
						);
						$messages->send(ACCESS_TOKEN, $request_params);
                    }else{
                        if(!isset($payload['grade'])){
                            $key_brd = ['one_time'=> true, 'buttons' => []];
                            for($i = 1; $i <= 4; ++$i){
                                $lines = [];
                                for ($k = 1; $k <= 3; ++$k){
                                    $grade = $payload['group'].'-'.$i.$k;
                                    $lines[] = $controller->getButton($grade, 'default', ['command' => 'change_group', 'group' => $payload['group'], 'grade' => $i.$k]);
                                }
                                $key_brd['buttons'][] = $lines;
                            }
                            $key_brd['buttons'][] = [$controller->getButton("\xF0\x9F\x94\x99Назад", 'primary', ['command' => 'change_group'])];
                            $key_brd['buttons'][] = [$controller->getButton("\xF0\x9F\x94\x99Главное меню", 'negative', ["command" => "back"])];
							$request_params = array(
								'message' => "Выберите группу: \n\n0 - Вернуться в главное меню",
								'peer_id' => $user_id,
								'random_id' => time(),
								'keyboard' => json_encode($key_brd, JSON_UNESCAPED_UNICODE)
							);
							$messages->send(ACCESS_TOKEN, $request_params);
                        }else{
                            $group = $payload['group'].'-'.$payload['grade'];
                            if (Schedule::isValidGroup($group)){
                                $controller->setGroup($user_id, $group);
                                $controller->setWindow($user_id, Schedule::SCHEDULE);
                                $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                            }
                        }
                    }
                    break;
                case 'hide_keyboard':
                    $controller->setKeyboardEnabled($user_id, false);
                    $controller->setWindow($user_id, Schedule::MAIN);
                    $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN), "");
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
                                $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                            }else{//иначе просим установить
                                $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                                $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard));
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
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "3":
                            $this->sendMessage($user_id,
                                "Штампы: ",
                                "doc-128463549_464176572,doc-128463549_464176550,doc-128463549_464176459,doc-128463549_464176424");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "4":
                            $this->sendMessage($user_id,
                                "Миссия колледжа: \n".
                                "Подготовка специалистов, обладающих профессиональными компетенциями, отвечающих современным требованиям рынка труда, \n".
                                "готовых к непрерывному росту, социальной и профессиональной мобильности, обладающих высокими духовно-нравственными качествами.\n");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "5":
                            $this->sendMessage($user_id,
                                "Видение колледжа: \n".
                                "Сохраняя традиции и внедряя инновации, колледж будет являться гарантом качественного образования, \n".
                                "обеспечивающего возможность карьерного роста и достойного положения в обществе");
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "6":
                            $this->sendMessage($user_id, $controller->getWeather());
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "*":
                            if($keyboard = $controller->isKeyboardEnabled($user_id)){//Текстовый режим
                                $controller->setKeyboardEnabled($user_id, false);
                                $keyboard = false;
                            }else{//Клавиатурный режим
                                $controller->setKeyboardEnabled($user_id, true);
                                 $keyboard = true;
                            }
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        default:
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                    }
                    break;
                case Schedule::SCHEDULE:
                    /*
                     * 1 - Пары на сегодня
                     * 2 - Пары на завтра
                     * 3 - Пары на определённую дату
                     * 4 - Сменить группу
                     * 0 - Вернуться в меню
                     */
                    switch ($text){
                        case "0":
                            $controller->setWindow($user_id, Schedule::MAIN);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                            break;
                        case "1":
                            $group = $controller->getGroup($user_id);
                            $date = new \DateTime("now");
                            $this->sendMessage($user_id,
                                Schedule::getSchedule($group, $date->format("Y-m-d"))."\n");
                                $this->sendMessage($user_id,str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                            break;
                        case "2":
                            $group = $controller->getGroup($user_id);
                            $date = new \DateTime("now");
                            $date->modify('+1 day');
                            $this->sendMessage($user_id, Schedule::getSchedule($group, $date->format("Y-m-d"))."\n");
                            $this->sendMessage($user_id, str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                            break;
                        case "3":
                            $controller->setWindow($user_id, Schedule::SCHEDULE_DATE);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_DATE, $keyboard));
                            break;
                        case "4":
                            $controller->setWindow($user_id, Schedule::SCHEDULE_GROUP);
                            $this->sendMessage($user_id, $controller->getWindowText(Schedule::SCHEDULE_GROUP, $keyboard));
                            break;
                        default:
                            $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                            break;
                    }
                    break;
                case Schedule::SCHEDULE_DATE://расписание по дате
                    if($text === "0"){
                        $controller->setWindow($user_id, Schedule::MAIN);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
                    }elseif(($d = date_create_from_format("Y-m-d", $text))){//Если сторока является датой в формате Y-m-d
                        $date = date_format($d, 'Y-m-d');
                        $group = $controller->getGroup($user_id);
                        $controller->setWindow($user_id, Schedule::SCHEDULE);
                        $this->sendMessage($user_id,
                            Schedule::getSchedule($group, $date)."\n\n".
                            str_replace("{group}", $group, $controller->getWindowText(Schedule::SCHEDULE, $keyboard)));
                        return;
                    }else{
                        $this->sendMessage($user_id, str_replace("{group}", $controller->getGroup($user_id), $controller->getWindowText(Schedule::SCHEDULE_DATE, $keyboard)));
                    }
                    break;
                case Schedule::SCHEDULE_GROUP://установка группы
                    if($text === "0"){
                        $controller->setWindow($user_id, Schedule::MAIN);
                        $this->sendMessage($user_id, $controller->getWindowText(Schedule::MAIN, $keyboard));
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
        $vk = new VKApiClient(API_VERSION);
        $messages = $vk->messages();
        $controller = $this->controller;

        $request_params = array(
            'message' => $message,
            'peer_id' => $user_id,
            'random_id' => time()
        );
        if($user_id > 2000000000){
            unset($request_params["peer_id"]);
            $request_params["chat_id"] = $user_id - 2000000000;
        }else{
            $request_params["user_id"] = $user_id;
        }

        if(empty($keyboard) and $user_id < 2000000000){
			if($this->controller->isKeyboardEnabled($user_id)){
				$request_params['keyboard'] = json_encode($controller->getKeyboard($controller->getWindow($user_id)), JSON_UNESCAPED_UNICODE);
			}else{
				$request_params['keyboard'] = '{"buttons":[],"one_time":true}';//спрятать клавиатуру
			}
        }

        if($attachment !== ""){
            $request_params['attachment'] = $attachment;
        }

        $messages->send(ACCESS_TOKEN, $request_params);
    }
}