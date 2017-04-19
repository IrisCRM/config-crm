<?php

namespace Iris\Config\CRM\sections\Email;

use Config;
use Iris\Iris;
use PDO;

class EmailFetcher extends Config
{
    private $supportEmails = array();

    function __construct()
    {
        parent::__construct([
            'common/Lib/lib.php',
            'common/Lib/access.php',
            'sections/Email/lib/rfc822_addresses.php',
            'sections/Email/lib/mime_parser.php',
        ]);

        $supportEmails = $this->getSupportEmails();
    }
    
    protected function getSupportEmails() {
        $con = $this->connection;
        $result = array();

        $res = $con->query("select stringvalue as value from iris_systemvariable where code='support_email_addresses'")->fetchAll(PDO::FETCH_ASSOC);
        $emails = $res[0]['value'];
        if ($emails != '') {
            $emails = iris_str_replace(' ', '', $emails);
            $result = explode(',', $emails);
        }

        return $result;
    }

    public function fetchEmail() {
        $con = $this->connection;

        $this->debug('fetchEmail begin');
        $res = $con->query("select address, port, encryption, login, password, id, last_id, last_n from iris_emailaccount where isactive='Y' and isuseimap <> 1")->fetchAll(PDO::FETCH_ASSOC);
        $cnt = 0;
        foreach ($res as $row) {
            $this->debug($row['address'].' ['.$row['login'], ']-------------------------- new mailbox', 'warn');
            if (php_sapi_name() == "cli") {
                echo '---- new mailbox ---- ' . $row['address'] . ' - '. $row['login'] . chr(10);
            }
            $result = $this->readNewMessages($row['address'], $row['port'], $row['encryption'], $row['login'], $row['password'], $row['id'], $row['last_id'], $row['last_n']);
            if (!$result['isSuccess']) {
                return $result;
            }
            $cnt += $result['messagesCount'];
        }

        return array("isSuccess" => true, "messagesCount" => $cnt);
    }

    protected function debug($message, $group = '') {
    }

    /**
     * Читает новые письма с ящика
     */
    protected function readNewMessages($p_address, $p_port, $p_encrypton, $p_login, $p_pwd, $p_accountID, $p_last_uid, $p_last_n) {
        $con = $this->connection;

        // информация о ящике
        $address = $p_address;
        if (($p_encrypton != 'no') and ($p_encrypton != '')) {
            $address = $p_encrypton . '://' . $address; // miv 06.09.2010: для работы с gmail
        }
        $port = (int)$p_port;
        $login = $p_login;
        $password = $p_pwd;

        $messages_count = 0;
        try {
            // считываем размер памяти, выделяемой скрипту
            $script_memory_limit = ((int)ini_get('memory_limit'))*1024*1024;

            // Создаем и соединяем сокет к серверу
            $this->debug('connectiong to server...');
            $socket = fsockopen($address, $port, $errno, $errstr);
            if (!$socket) {
                $this->debug('fsockopen("'.$address.'", "'.$port.'") failed: '.$errno.' - '.$errstr);
                return array("isSuccess" => false, "messagesCount" => 0, "errorMessage" => "fsockopen to $address failed");
            }
            $this->debug('connected');

            // Читаем информацию о сервере
            $this->readPOP3Answer($socket);

            // Делаем авторизацию
            $this->debug('auth');
            $this->writePOP3Response($socket, 'USER '.$login);
            $this->readPOP3Answer($socket); // ответ сервера

            $this->writePOP3Response($socket, 'PASS '.$password);
            $dummy_answer = $this->readPOP3Answer($socket); // ответ сервера
            $this->debug($dummy_answer, 'server response');

            // Считаем количество сообщений в ящике и общий размер
            $this->writePOP3Response($socket, 'STAT');
            $answer = $this->readPOP3Answer($socket); // ответ сервера
            //$this->debug($answer, 'STAT');
            $answer_arr = explode(' ', $answer);
            $total_count = $answer_arr[1];
            $this->debug($total_count, 'total messages');
            if ($total_count == 0) {
                // если сообщений нет, то перейдем к следующему ящику
                return array("isSuccess" => true, "messagesCount" => 0);
            }

            // проверим, поддерживается ли команда UIDL
            $this->writePOP3Response($socket, 'UIDL');
            $buffer = fread($socket,3);
            if ($buffer != '+OK') {
                return array("isSuccess" => false, "messagesCount" => 0, "errorMessage" => "command UIDL for $address is not supported");
            }

            // если поддерживается, то считаем список UID писем, находящихся в ящике
            $uids_string = $this->getPOP3Data($socket);


            $uids_arr = explode("\r\n", $uids_string);
            $i=0;
            //foreach ($uids_arr as $elem)
            //	$uids_arr[$i++] = explode(" ", $elem);
            unset($uids_arr[$i-1]); // удаляем последний элемент массива, которыя является пустым
            // в uids_arr находится массив, елементы которого являются массивом. 0 - номер письма, 1 - его uid (uid уникален в пределах почтового яшика)
            // miv 01.10.2010: теперь в uids_arr содержится массив со строкой '<n> <uid>' (так нужно для ускорения считываения)

            //$this->debug($uids_arr, 'message uids array');
            // находим ответственного по ящику
            $OwnerID = null;

            $owncnt_res = $con->query("select count(id) from iris_emailaccount_defaultaccess where EmailAccountID='".$p_accountID."' and r='1'")->fetchAll();
            if ($owncnt_res[0][0] == 1) {
                $own_res = $con->query("select contactid from iris_emailaccount_defaultaccess where EmailAccountID='".$p_accountID."' and r='1'")->fetchAll();
                if ($own_res[0][0] != '') {
                    $OwnerID = $own_res[0][0];
                }
            }

            // Считаем список uid писем, содержащихся в БД, и, сравнив массивы, вычленим из uids_arr только новые письма
            // это ускорит проверку, так как будут сразу загружаться новые письма или добавляться в доступ, если письмо загружено с другого аккаунта
            $db_uids_array = $con->query("select '1 ' || uid as uid from iris_emailrecieved where emailaccountid='".$p_accountID."'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($db_uids_array as $key => $value)
                $db_uids_array[$key] = $value['uid'];

            $uid_server_ca = $this->createUIDLCompareArray($uids_arr);
            $uid_db_ca = $this->createUIDLCompareArray($db_uids_array);
            $uid_compared = array_diff_key($uid_server_ca, $uid_db_ca);

            $uids_only_new_array = array();
            foreach ($uid_compared as $key=>$val) {
                $uids_only_new_array[] = array($val, $key);
            }
            $uids_arr = $uids_only_new_array;

            // просмотрим каждый элемент массива uid
            $this->debug('checking uids array');
            foreach ($uids_arr as $uid_item) {
                $this->debug($uid_item[0].' '.$uid_item[1], 'selected message', 'info');
                // если данный uid уже есть в iris_mailrecieved для данного аккаунта, то письмо старое, пропустим его

                $res = $con->query("select id from iris_emailrecieved where uid='".$uid_item[1]."' and emailaccountid='".$p_accountID."'")->fetchAll();
                if ($res[0][0] != '')
                    continue; // если письмо есть, то пропустим его и не будем загружать
                // TODO: это не ускоряет
                //if (in_array($uid_item[1], $db_uids_array))
                //	continue; // если письмо есть, то пропустим его и не будем загружать


                // если нет, то оно не обязательно является новым - может быть оно пришло с другого аккаунта (письмо могло быть выслано сразу нескольким пользователем)
                // можно в принципе при получении письма проставлять в iris_mailrecieved строки для всех аккаунтов на которые оно пришло и тогда проверять не нужно. но если в процессе работы добавлен аккаунт, то тогда может замусориться "новыми" письмами
                // проверим, есть ли данное письмо в БД. для этого счиатем его message-id при помощи команды TOP и найдем его в iris_mailrecieved
                $this->debug('TOP '.$uid_item[0].' 0');
                $this->writePOP3Response($socket, 'TOP '.$uid_item[0].' 0');
                /*
                            $buffer = fread($socket,3); // проверим, поддерживается ли команда TOP
                            if ($buffer != '+OK') {
                                echo '{"error": "command TOP is not supported", "messages_count": "-1", "email_account": "'.$login.'('.$address.')'.'"}';
                                return -1;
                            }
                */
                $answer = $this->getPOP3Data($socket); // считали заголовок письма номер $uid_item[0]

                $mime = new \mime_parser_class;
                $mime->mbox = 1;
                $mime->decode_bodies = 0;
                $mime->ignore_syntax_errors = 1;

                $parameters=array('Data'=>$answer, 'SkipBody'=>1);
                if(!$mime->Decode($parameters, $decoded)) {
                    continue;
                }
                $message_id = $decoded[0]["Headers"]["message-id:"]; // получили message-id письма
                if (($message_id == '') or ($message_id == null) or ($message_id == 'NULL')) {
                    // если message-id пусто, то сгенерируем его на основе других заголовков (дата, от, кому, тема)
                    $message_id = $decoded[0]["Headers"]["date:"].$decoded[0]["Headers"]["from:"].$decoded[0]["Headers"]["to:"].$decoded[0]["Headers"]["subject:"];
                    $this->debug('Warning!! message-id is null and was generated from other headers');
                }
                $message_id = substr($message_id, 0, 1800); // обрезаем строку до длинны поля

                $this->debug($message_id, 'message-id');
                $this->debug($decoded[0]["Headers"]["subject:"], 'message subject');
                if ($decoded[0]["Headers"]["subject:"] == '')
                    $this->debug('message subject is null');

                //$res = $con->query("select emailid from iris_emailrecieved where messageid='".$message_id."'")->fetchAll();
                // miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
                $cmd = $con->prepare("select emailid from iris_emailrecieved where messageid=:messageid");
                $cmd->execute(array(":messageid" => $message_id));
                $res = $cmd->fetchAll();

                if ($res[0][0] != '') {
                    $this->debug('this message is old and was loaded from another account');
                    // если письмо есть, то оно не новое и нужно добавить права на данное письмо в соответсвии с текущим аккаунтом
                    // TODO: если уже есть то заменить?..
                    $this->addAccessInformation($res[0][0], '', $p_accountID);

                    // также добавим запись в iris_mailrecieved, что uid письма для данной учетной записи уже получим и сделаем ссылку на существующее письмо
                    // сохраняем id письма в системе чтобы исключить его повторную загрузку
                    //$cmd=$con->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values ('".create_guid()."', '".$res[0][0]."', '".$message_id."', '".$p_accountID."', '".$uid_item[1]."')");
                    //$cmd->execute();
                    // miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
                    $cmd=$con->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values (:id, :emailid, :messageid, :emailaccountid, :uid)");
                    $iris_emailrecieved_id = create_guid();
                    $cmd->execute(array(":id" => $iris_emailrecieved_id, ":emailid" => $res[0][0], ":messageid" => $message_id, ":emailaccountid" => $p_accountID, ":uid" => $uid_item[1]));

                    //$this->debug($cmd->errorCode(), 'iris_emailrecieved insert code');
                    $messages_count++;
                    continue; // переходим к следующему письму
                } else {
                    $this->debug('this message is new');
                    // если письма нет, то оно действительно новое, выполним 2+4 действия:

                    // 0.1) если это "долгое" письмо, то не будем его загружать
                    $message_is_long_flag = 0; // флаг того, что письмо "долгое" (загружается больше чем time_limit)
                    $email_read_count = 1; // число попыток считывания данного письма с этого ящика
                    if ($p_last_uid == $message_id) {
                        $email_read_count+=$p_last_n;
                        if ($p_last_n >= 2)
                            $message_is_long_flag = 1;
                    }
                    //$emailaccount_update_sql = "update iris_emailaccount set last_id='".$message_id."', last_n=".$email_read_count." where id='".$p_accountID."'";
                    //$cmd=$con->prepare($emailaccount_update_sql);
                    //$cmd->execute();
                    $emailaccount_update_sql = "update iris_emailaccount set last_id=:last_id, last_n=:last_n where id=:id";
                    $cmd=$con->prepare($emailaccount_update_sql);
                    $cmd->execute(array("last_id" => $message_id, "last_n" => $email_read_count, "id" => $p_accountID));

                    if ($cmd->errorCode() != '00000') {
                        $this->debug($emailaccount_update_sql, 'update long emailsql');
                        $this->debug($cmd->errorInfo(), 'update long email status');
                    }

                    // 0.2) если скрипт имеет ограничение на размер выделяемой ему памяти, то узнаем размер письма
                    $message_is_big_flag = 0; // флаг того, что письмо "большое" (его размер не может поместиться в память, выделенную php скрипту)
                    if ($script_memory_limit > 0) {
                        $this->writePOP3Response($socket, 'LIST '.$uid_item[0]);
                        $answer = $this->readPOP3Answer($socket); // ответ сервера
                        $email_size = explode(' ', $answer);
                        $email_size = $email_size[2];
                        $needed_size = (int)$email_size + memory_get_usage();
                        if ($needed_size > $script_memory_limit) {
                            $this->debug('size='.$needed_size.' limit='.$script_memory_limit, 'message is big');
                            $message_is_big_flag = 1;
                        }
                    }

                    // 1) считаем письмо с сервера (если оно "влезет" и "не долгое")
                    if (($message_is_long_flag == 0) and ($message_is_big_flag == 0)) {
                        $this->writePOP3Response($socket, 'RETR '.$uid_item[0]);
                        $answer = $this->getPOP3Data($socket);
                    } else {
                        // если письмо "не влезет" или "долгое", то загрузим только его заголовок
                        $answer = $this->getEmailTemplate($decoded[0]["Headers"], $message_is_big_flag);
                        $this->debug('this message is big('.$message_is_big_flag.') or long('.$message_is_long_flag.') ...', 'skipping message body');
                    }

                    // 2) загрузим письмо в БД
                    $this->debug('saving into db...');
                    $EmailID = $this->saveEmail($answer, $OwnerID, $p_accountID); // сохраняем письмо в системе
                    $this->debug('saving ok');
                    // 3) добавим запись в iris_mailrecieved о новом письме, чтобы исключить его повторную загрузку
                    //$cmd=$con->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values ('".create_guid()."', '".$EmailID."', '".$message_id."', '".$p_accountID."', '".$uid_item[1]."')");
                    //$cmd->execute();
                    $cmd=$con->prepare("insert into iris_emailrecieved(id, emailid, messageid, emailaccountid, uid) values (:id, :emailid, :messageid, :emailaccountid, :uid)");
                    $iris_emailrecieved_id = create_guid();
                    $cmd->execute(array("id" => $iris_emailrecieved_id, "emailid" => $EmailID, "messageid" => $message_id, "emailaccountid" => $p_accountID, "uid" => $uid_item[1]));

                    //$this->debug($cmd->errorCode(), 'iris_emailrecieved insert code');
                    // 4) проставим права и связи для письма
                    $this->addAccessInformation('', $message_id, $p_accountID);

                    $messages_count++;
                }
            }

            // Отсоединяемся от сервера
            $this->writePOP3Response($socket, 'QUIT');
            $this->readPOP3Answer($socket); // ответ сервера
        } catch (Exception $e) {
            echo "\nError: ".$e->getMessage();
        }

        if (isset($socket)) {
            fclose($socket);
        }

        return array("isSuccess" => true, "messagesCount" => $messages_count);
    }

    /**
     * Функция для отправки запроса серверу
     */
    protected function writePOP3Response($socket, $msg) {
        $msg = $msg."\r\n";
        fwrite($socket, $msg);
    }

    /**
     * Функция для чтения ответа сервера. Выбрасывает исключение в случае ошибки
     */
    protected function readPOP3Answer($socket, $top = false) {
        $read = fgets($socket);
        if ($top) {
            // Если читаем заголовки
            $line = $read;
            while (!ereg("^\.\r\n", $line)) {
                $line  = fgets($socket);
                $read .= $line;
            }
            $read .= fgets($socket);
        }
        if ($read{0} != '+') {
            if (!empty($read)) {
                throw new Exception('POP3 failed: '.$read."\n");
            } else {
                throw new Exception('Unknown error'."\n");
            }
        }
        return $read;
    }

    /**
     * Функция для чтения ответа сервера
     */
    protected function getPOP3Data($pop_conn) {
        $CRLF = "\r\n";
        $result = array();
        $buffer = "";
        while (true) {
            $buffer = fgets($pop_conn);
            $buffer = chop($buffer);
            if(trim($buffer) == ".") {
                break;
            }

            array_push($result, $buffer);
        }
        unset($result[0]); // remove answer header string (+OK -ERR)
        $result = implode($CRLF, $result);
        $result = $result . $CRLF;

        return $result;
    }

    protected function createUIDLCompareArray($p_uids_array) {
        $uids_list = array();
        foreach ($p_uids_array as $elem) {
            $elem_arr = explode(" ", $elem);
            if (isset($elem_arr[1]))
                $uids_list[$elem_arr[1]] = $elem_arr[0];
        }

        return $uids_list;
    }

    protected function saveEmail($p_text, $p_ownerID, $p_emailaccountID) {
        $mime=new \mime_parser_class;
        $con = $this->connection;

        // Set to 0 for parsing a single message file
        // Set to 1 for parsing multiple messages in a single file in the mbox format
        $mime->mbox = 1;

        // Set to 0 for not decoding the message bodies
        $mime->decode_bodies = 1;

        // Set to 0 to make syntax errors make the decoding fail
        $mime->ignore_syntax_errors = 1;

        $parameters=array(
            ///'File'=>'message.eml',
            'Data'=>$p_text,

            // Do not retrieve or save message body parts
            'SkipBody'=>0,
        );
        $this->debug('----begin parsing 1...');
        if(!$mime->Decode($parameters, $decoded)) {
            $this->debug('MIME message decoding error: '.$mime->error.' at position '.$mime->error_position);
            //$this->debug($p_text);
            return;
        }
        $this->debug('----begin parsing 2...');
        for($message = 0; $message < count($decoded); $message++) {
            if($mime->Analyze($decoded[$message], $results) == false) {
                $this->debug('MIME message analyse error: '.$mime->error);
                continue;
            }
            // от кого
            $email_from = iconv($results['From'][0]['Encoding'], GetDefaultEncoding(), $results['From'][0]['address']);
            // кому
            $email_to = '';
            if (is_array($results['To']) == true)
                foreach ($results['To'] as $res_to)
                    $email_to .= iconv($res_to['Encoding'], GetDefaultEncoding(), $res_to['address']).' ';
            $email_to = trim($email_to); // miv 14:52 01.11.2010: убираем пробел в конце

            // тема
            $this->debug($results['Subject'], 's1');
            $this->debug($results['Subject'][0], 's1_1');
            $this->debug($results['Subject'][1], 's1_2');
            if (is_array($results['Subject']) == true) {
                $subj = $results['Subject'][0];
            } else
                $subj = $results['Subject'];
            $this->debug($subj, 's2');
            $this->debug($results['SubjectEncoding'], 's21-encoding');

            //orig: $email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $results['Subject']);
            if (($results['SubjectEncoding'] == '') or ($results['SubjectEncoding'] == 'NULL'))
                $email_subject = $this->decodeAttachmentName($subj);
            else
                $email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $subj);
            $this->debug($email_subject, 's3');
            /*
                    $email_subject = iconv($results['SubjectEncoding'], GetDefaultEncoding(), $results['Subject']);
                    $this->debug($email_subject);
                    $email_subject = DecodeAttachmentName($email_subject); // для корявых тем
                    $this->debug($email_subject);
            */

            // тело ообщения
            $email_body = iconv($results['Encoding'], GetDefaultEncoding(), $this->removeBOM($results['Data'])); // miv 09.09.2010: для корректной работы utf8 убираем BOM

            if ($results['Type'] == 'text') {
                $email_body = iris_str_replace(chr(10), '<br>', $email_body);
            }

            // вставляем письмо
            $EmailID = create_guid();

            // тип письма - входящее
            $et_res = $con->query("select id from iris_emailtype where code='Inbox'")->fetchAll();
            // компания, контакт и ответсвенный
            $accountID = null;
            $contactID = null;
            $ownerID = null;
            $contact_sql = PerformMacroSubstitution("select id, accountid, ownerid from iris_contact where _iris_lower[email]='".strtolower($results['From'][0]['address'])."' or id in (select contactid from iris_contact_email where email='".strtolower($results['From'][0]['address'])."')");
            $contact_res = $con->query($contact_sql)->fetchAll();
            if ($contact_res[0][0] != '') {
                $contactID = $contact_res[0][0];
                $accountID = $contact_res[0][1];
                $ownerID = $contact_res[0][2];
            } else {
                $account_sql = PerformMacroSubstitution("select id, ownerid, primarycontactid from iris_account where _iris_lower[email]='".strtolower($results['From'][0]['address'])."' or id in (select accountid from iris_account_email where email='".strtolower($results['From'][0]['address'])."')");
                $account_res = $con->query($account_sql)->fetchAll();
                if ($account_res[0][0] != '') {
                    $contactID = $account_res[0][2]; // miv 12.12.2011: основной контакт компании
                    $accountID = $account_res[0][0];
                    $ownerID = $account_res[0][1];
                }
            }
            $p_ownerID = $ownerID; // miv 21.10.2010: ответсвенный теперь берется от контакта или компании, если он не указан

            // miv 01.11.2010: если письмо пришло на ящик саппорта, то автоматически создадим для него инцидент
            $support_emails = $this->supportEmails;
            if (in_array($email_to, $support_emails) == true) {
                $Number = GenerateNewNumber('IncidentNumber', null, $con); // номер будущего инцидента
                $incident_id = create_guid(); // id будущего инцидента
                // сформируем текстовое содержимое письма, которое не превышает 1000 символов
                $short_body = $email_body;
                $short_body = iris_str_replace(chr(13).chr(10), '', $short_body);
                $short_body = iris_str_replace(chr(10).chr(13), '', $short_body);
                $short_body = iris_str_replace('<br>', chr(10), $short_body);
                $short_body = iris_str_replace('<BR>', chr(10), $short_body);
                $short_body = strip_tags($short_body);
                if (iris_strlen($short_body) >= 1000)
                    $short_body = iris_substr($short_body, 0, 1000);

                // вставка инцидента
                $ins_inc_cmd = $con->prepare("insert into iris_incident (id, number, name, description, accountid, contactid, ownerid, date, incidentstateid, isremind, reminddate) values (:id, :number, :name, :description, :accountid, :contactid, :ownerid, now(), (select id from iris_incidentstate where code='Plan'), 1, now())");
                $ins_inc_cmd->execute(array(":id" => $incident_id, ":number" => $Number, ":name" => $email_subject, ":description" => $short_body, ":accountid" => $accountID, ":contactid" => $contactID, ":ownerid" => $p_ownerID));
                if ($ins_inc_cmd->errorCode() == '00000') {
                    // изменим тему письма, вставив в ее начало номер инцидента. тогда письмо при сохранении автоматически будет привязано к инциденту
                    $email_subject = '['.$Number.'] '.$email_subject;
                    // увеличим номер инцидента
                    UpdateNumber('Incident', $incident_id, 'IncidentNumber');
                    // вставка прав на инцидент
                    $acc_ins_sql  = "insert into iris_incident_access (id, recordid, contactid, r, w, d, a) ";
                    $acc_ins_sql .= "select iris_genguid(), :incidentid, contactid, r, w, d, a from iris_emailaccount_defaultaccess where emailaccountid=:emailaccountid";
                    $acc_ins_cmd = $con->prepare($acc_ins_sql);
                    $acc_ins_cmd->execute(array(":incidentid" => $incident_id, ":emailaccountid" => $p_emailaccountID));
                }
            }

            // miv 02.08.2010: привязка письма к инциденту
            if (iris_preg_match("/\\[\\d{6}-\\d+\\]/", $email_subject, $matches, PREG_OFFSET_CAPTURE)) {
                $this->debug($matches[0][0], 'Incident fetched:');
                $incident_number = trim($matches[0][0], "\x5B..\x5D"); // обрезаем скобки [ и ]
                $cmd = $con->prepare("select id as id from iris_incident where number = :number");
                $cmd->execute(array(":number" => $incident_number));
                $incident = $cmd->fetchAll(PDO::FETCH_ASSOC);
                $this->debug($incident[0]['id'], 'Incident id:');
            } else {
                $incident[0]['id'] = null;
            }

            //$insert_sql = "insert into iris_email(id, e_from, e_to, subject, body, emailtypeid, accountid, contactid, ownerid, messagedate) values (:id, :e_from, :e_to, :subject, :body, :emailtypeid, :accountid, :contactid, :ownerid, _iris_convert_datetimestring_to_date[".$results['Date']."])";
            //$insert_sql = PerformMacroSubstitution($insert_sql);
            // miv 22.09.2005: если дата в письме не указана, то макроподстановка выдает ошибку, вставка пустой строки вместо даты тоже
            /*
                    if (substr($results['Date'], 3, 1) == ',')
                        $date_length = 25;
                    else
                        $date_length = 20;

                    $emaildatestr = trim(substr($results['Date'], 0, $date_length));
            */
            $emaildatestr = $results['Date'];
            $messageDateStr = date('d.m.Y H:i:s', strtotime(substr($emaildatestr, 5))); // miv 13.12.2011: переводим с учетом часового пояса
            if ($messageDateStr == '') {
                $messageDateStr = date('d.m.Y H:i:s');
            }
            $insert_sql = "insert into iris_email(id, createdate, e_from, e_to, subject, body, emailtypeid, accountid, contactid, ownerid, messagedate, incidentid) values (:id, now(), :e_from, :e_to, :subject, :body, :emailtypeid, :accountid, :contactid, :ownerid, to_timestamp('".$messageDateStr."', 'DD.MM.YYYY HH24:MI:SS'), :incidentid)";

            $cmd=$con->prepare($insert_sql);
            $cmd->bindParam(":id", $EmailID);
            $cmd->bindParam(":e_from", $email_from);
            $cmd->bindParam(":e_to", $email_to);
            $cmd->bindParam(":subject", $email_subject);
            $cmd->bindParam(":body", $email_body);
            $cmd->bindParam(":emailtypeid", $et_res[0][0]);
            $cmd->bindParam(":accountid", $accountID);
            $cmd->bindParam(":contactid", $contactID);
            $cmd->bindParam(":ownerid", $p_ownerID);
            $cmd->bindParam(":incidentid", $incident[0]['id']);
            $cmd->execute();
            $this->debug($cmd->errorInfo(), 'inserting email status', 'info');
            if ($cmd->errorCode() != '00000')
                $this->debug('email is not inserted!');


            // расширяем массивы прикрепленных файлов и inline изображений
            if (!isset($results['Attachments'])) {
                $results['Attachments'] = array();
            }
            if (!isset($results['Related'])) {
                $results['Related'] = array();
            }

            $attachments_and_related =
                array_merge($results['Attachments'], $results['Related']);

            // вставляем прикрепленные файлы и изображения
            foreach($attachments_and_related as $attachment) {
                $this->debug('attachment');
                $attachment_info = $this->insertAttachment($attachment, array(
                    "emailid" => $EmailID,
                    "accountid" => $accountID,
                    "contactid" => $contactID,
                    "ownerid" => $p_ownerID,
                ));

                if ($attachment_info["isInline"]) {
                    $this->updateInlineImagesLinks($EmailID, $attachment_info);
                }
            }
        }

        $this->debug('----end parsing...');
        for($warning = 0, Reset($mime->warnings); $warning < count($mime->warnings); Next($mime->warnings), $warning++)	{
            $w = Key($mime->warnings);
            $this->debug('Warning: '.$mime->warnings[$w].' at position '.$w);
        }
        return $EmailID;
    }

    protected function removeBOM($str="")
    {
        if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
            $str=substr($str, 3);
        }
        // miv 23.03.2011: в письмах outlook есть "вредные символы" в стилях из-за которых письмо не переводится в нужную кодировку в iconv
        $str = str_replace(array(pack("CCC",0xef,0x82,0xb7), pack("CCC",0xef,0x82,0xa7)), array('', ''), $str);
        return $str;
    }

    protected function insertAttachment($attachment, $params) {
        $con = $this->connection;
        $file_real_name = create_guid();
        $file_path = Iris::$app->getRootDir() . 'files/' . $file_real_name;
        $this->debug($file_path, '--------------------filename');
        file_put_contents($file_path, $attachment['Data']);
        $attachment_name = $this->decodeAttachmentName($attachment["FileName"]);

        $sql = "insert into iris_file (id, createdate, file_file, file_filename, ".
            "EmailID, AccountID, ContactID, OwnerID, filestateid, date) values ".
            "(:id, now(), :file_file, :file_filename, :EmailID, :AccountID, ".
            ":ContactID, :OwnerID, ".
            "(select id from iris_filestate where code = 'Active'), now())";
        $fileId = create_guid();
        $cmd = $con->prepare($sql);
        $cmd->bindParam(":id", $fileId);
        $cmd->bindParam(":file_file", $file_real_name);
        $cmd->bindParam(":file_filename", $attachment_name);
        $cmd->bindParam(":EmailID", $params['emailid']);
        $cmd->bindParam(":AccountID", $params['accountid']);
        $cmd->bindParam(":ContactID", $params['contactid']);
        $cmd->bindParam(":OwnerID", $params['ownerid']);
        $cmd->execute();
        $this->debug($cmd->errorInfo(), 'inserting email attachment status', 'info');

        return array(
            "isInline" => isset($attachment['ContentID']),
            "file_id" => $fileId,
            "ContentID" => $attachment['ContentID']
        );
    }

    protected function updateInlineImagesLinks($emailid, $attachment_info) {
        $con = $this->connection;

        if (!isset($attachment_info['ContentID'])) {
            return;
        }
        $findFromString = 'cid:' . $attachment_info['ContentID'];
        $replaceToString =
            "web.php?_func=DownloadFile&table=iris_File&".
            "id=".$attachment_info["file_id"]."&column=file_file";

        $cmd = $con->prepare("select body from iris_email where id = :id");
        $cmd->bindParam(":id", $emailid);
        $cmd->execute();
        $emails = $cmd->fetchAll(PDO::FETCH_ASSOC);

        $body = $emails[0]["body"];
        $body = str_replace($findFromString, $replaceToString, $body);

        $cmd = $con->prepare("update iris_email set body = :body where id = :id");
        $cmd->bindParam(":id", $emailid);
        $cmd->bindParam(":body", $body);
        $cmd->execute();
    }

    /**
     * Декодирует имя вложения из нужной кодировки
     */
    protected function decodeAttachmentName($p_str) {
        $filename_encoding = $this->detectCyrillicCharset($p_str);
        switch ($filename_encoding) {
            case 'i':
                $filename_decoded = UtfDecode($p_str);
                break;
            case 'k':
                $filename_decoded = convert_cyr_string($p_str, 'k' , 'w');
                break;
            case 'w':
                $filename_decoded = $p_str;
                break;
        }
        if ($filename_decoded == '')
            $filename_decoded = $p_str;
        return $filename_decoded;

    }

    /**
     * Определяет кодировку строки
     */
    protected function detectCyrillicCharset($str) {
        $charsets = Array('k' => 0, 'w' => 0, 'd' => 0, 'i' => 0, 'm' => 0);
        $lowercase = 3;
        $uppercase = 1;

        for ( $i = 0, $length = strlen($str); $i < $length; $i++ ) {
            $char = ord($str[$i]);
            //non-russian characters
            if ($char < 128 || $char > 256) continue;

            //CP866
            if (($char > 159 && $char < 176) || ($char > 223 && $char < 242)) $charsets['d']+=$lowercase;
            if (($char > 127 && $char < 160)) $charsets['d']+=$uppercase;

            //KOI8-R
            if (($char > 191 && $char < 223)) $charsets['k']+=$lowercase;
            if (($char > 222 && $char < 256)) $charsets['k']+=$uppercase;

            //WIN-1251
            if ($char > 223 && $char < 256) $charsets['w']+=$lowercase;
            if ($char > 191 && $char < 224) $charsets['w']+=$uppercase;

            //MAC
            if ($char > 221 && $char < 255) $charsets['m']+=$lowercase;
            if ($char > 127 && $char < 160) $charsets['m']+=$uppercase;

            //ISO-8859-5
            if ($char > 207 && $char < 240) $charsets['i']+=$lowercase;
            if ($char > 175 && $char < 208) $charsets['i']+=$uppercase;

        }
        arsort($charsets);
        return key($charsets);
    }

    protected function addAccessInformation($p_EmailID, $p_MessageID, $p_accountID, $p_access_mode = 'default') {
        $con = $this->connection;
        $this->debug('== AddAccessInformation begin');
        // проверка того, что права доступа на таблицу включены
        $res = $con->query("select id from iris_table where code='iris_email' and is_access='1'")->fetchAll();;
        if ($res[0][0] == '')
            return; // если права на записи не установлены то выйдем

        // получение id письма
        if ($p_EmailID != '') {
            $rec_id = $p_EmailID;
        } else {
            //$email_res = $p_con->query("select emailid from iris_emailrecieved where messageid='".$p_MessageID."'")->fetchAll();
            // miv 13:07 01.09.2009: иногда в message_id встречаются кавычки, поэтому select теперь через параметр
            $cmd = $con->prepare("select emailid from iris_emailrecieved where messageid=:messageid");
            $cmd->execute(array(":messageid" => $p_MessageID));
            $email_res = $cmd->fetchAll();

            $rec_id	= $email_res[0][0];
        }

        $this->debug($p_access_mode, 'p_access_mode');

        $access_res = $con->query("select contactid, r, w, d, a from iris_emailaccount_".$p_access_mode."access where emailaccountid='".$p_accountID."'")->fetchAll();
        foreach ($access_res as $access_row) {
            // вставка прав на письмо
            $l_user_access_sql = "insert into iris_email_access (ID, RecordID, ContactID, R, W, D, A) values ('".create_guid()."', '".$rec_id."', '".$access_row['contactid']."', '".$access_row['r']."', '".$access_row['w']."', '".$access_row['d']."', '".$access_row['a']."')";
            $this->debug($l_user_access_sql, 'email sql');
            $con->exec($l_user_access_sql);
            $this->debug($con->errorInfo(), 'inserting email access');

            // вставка прав на файлы
            $email_attachment_res = $con->query("select id from iris_file where emailid='".$rec_id."'")->fetchAll();
            foreach ($email_attachment_res as $email_attachment) {
                $l_user_access_attachment_sql = "insert into iris_file_access (ID, RecordID, ContactID, R, W, D, A) values ('".create_guid()."', '".$email_attachment[0]."', '".$access_row['contactid']."', '".$access_row['r']."', '".$access_row['w']."', '".$access_row['d']."', '".$access_row['a']."')";
                $this->debug($l_user_access_attachment_sql, 'attachment sql');
                $con->exec($l_user_access_attachment_sql);
                $this->debug($con->errorInfo(), 'inserting attachments access');
            }
        }
        $this->debug('== AddAccessInformation end');
    }

    protected function getEmailTemplate($p_headers_array, $p_message_is_big_flag) {
        $mail_str  = 'Date: '.$p_headers_array['date:'].chr(10);
        $mail_str .= 'To: '.$p_headers_array['to:'].chr(10);
        $mail_str .= 'From: '.$p_headers_array['from:'].chr(10);
        $mail_str .= 'Subject: '.$p_headers_array['subject:'].chr(10);
        $mail_str .= 'Message-ID: '.$p_headers_array['message-id:'].chr(10);
        $mail_str .= 'Content-Type: text/html; charset = "'.GetDefaultEncoding().'"'.chr(10);
        $mail_str .= 'Content-Transfer-Encoding: 8bit'.chr(10);
        $mail_str .= chr(10);;
        $mail_str .= chr(10);;
        if ($p_message_is_big_flag == 1) {
            $mail_str .= 'Данное письмо не может быть загружено в систему, так как оно превышает максимально допустимый размер';
        } else {
            $mail_str .= 'Данное письмо не может быть загружено в систему, так время его загрузки превысило максимально допустимое значение';
        }

        return $mail_str;
    }
}
