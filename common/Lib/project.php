<?php

// функция получения последнего активного проекта клиента. копия функции SetProject из раздела сообщения
// TODO: нужно сделать чтобы все работало от этой функции и она задавала бы единый алгоритм
// mnv: сделал наоборот от контактов, получилось универсально
function GetRecentProject($p_client_id, $p_con=null, $result = null) {
	$con = GetConnection($p_con);
	$result = null;
	
	// BUG 090710-441 теперь берется проект у которого последнее новое связанное сообщение или связанный файл
	$query = $con->prepare("select id, name from iris_project where id=iris_getlastproject(:contactid)");
	$query->execute(array(":contactid" => $p_client_id));
	$query_res = $query->fetchAll(PDO::FETCH_ASSOC);
	
	if ($query_res) {
		$result = FieldValueFormat('ProjectID', $query_res[0]['id'], $query_res[0]['name'], $result);
	}
	
	return $result;
}



//  функция автоматически определяет нового ответственного
//  используется в confirm.php, question.php, [c_message.php], c_project.php, files(?)
//  Ответственный за заказ вычисляется по следующей формуле: 
//  берется тот ответственный, у которого меньше всего активных заказов и максимальная квалификация. 
//  Пусть N — число всех активных заказов. Ni — число активных заказов у сотрудника, N = sum(Ni). 
//  Процент заказов у сотрудника Pi=100*Ni/N, N>0; Pi = 0, N=0. 
//  Пусть квалификация сотрудника Ki. 
//  Вычисляем по сотрудникам коэффициент эффективности выбора отрудника Ei = a*Ki/b+Pi; a=1, b=100. 
//  Берем сотрудника с максимальным Ei.
//Верно так:
//Ei = a*Ki/(Pi*b), a=1, b=100.
function GenerateNewOwner($p_con, $p_productid='', $p_mode=0) {
//ini_set('display_errors', 'on');
// менеджеры
// тип - сотрудник
// группа - менеджеры

// услуга -> квалификация
// активный заказ (ы)
//	projectstateid
//	"Plan"
//	"Execute"
//$p_productid = '1ed8c9d27-d598-47e2-93c3-bd2699f172f7';


	$sql  = "select T0.id as id, T0.name as name, ";
	$sql .= " (select count(T3.id) from iris_project T3 left join iris_projectstate T4 on T3.projectstateid = T4.id where T4.code in ('Plan', 'Execute') and T3.ownerid = T0.id) as pcnt, ";
	$sql .= " (select count(T5.id) from iris_account T5 left join iris_accounttype T6 on T5.accounttypeid = T6.ID where T5.ownerid = T0.id and T6.code='Client') as acnt ";
	//	$sql .= " (select qualification from iris_contact_competence TC where TC.contactid = T0.id and TC.productid = '".$p_productid."') as qual";
	$sql .= " from iris_contact T0";
	$sql .= " left join iris_contacttype T1 on T0.contacttypeid = T1.id";
	$sql .= " left join iris_accessrole T2 on T0.accessroleid = T2.id";
	$sql .= " where T1.code='Your' and T0.isclientdistribution=1";
//	echo '+'.$sql.'+';
	$sql_res = $p_con->query($sql)->fetchAll();
	$proj_count = 0;
	$account_count = 0;
	$e = -1;
	$contactname = '';
	$contactid = '';
	foreach ($sql_res as $row) {
		$proj_count += $row['pcnt']; // считаем общее число проектов
		$account_count += $row['acnt']; // считаем общее число клиентов
	}

	$a = 1;
	$b = 5;
	foreach ($sql_res as $row) {
//		$p_i = ((int)$row['pcnt'] + 1)/100; // добавлена 1 чтобы если у менеджера нет заказов не было деления на 0 и это отличалась если бы у него есть 1 заказ
		$p_i = ((int)$row['pcnt'] + 1)/($proj_count+1); // добавлена 1 чтобы если у менеджера нет заказов не было деления на 0 и это отличалась если бы у него есть 1 заказ
//		$k_i = (int)$row['qual'];
		$a_i = ((int)$row['acnt'] +1)/($account_count+1);
		$n   = $proj_count;
//		$e_i = $k_i/($p_i*100);
		$e_i = $a/$a_i + $b/$p_i;
		if ($e_i > $e) {
			$e = $e_i;
			$contactname = $row['name'];
			$contactid = $row['id'];
		}
	}
	
//$contactname = 'Менеджер [1]!';
//$contactid = '541c8169-01cc-f7a2-e52e-ed93c1c3647b';
	
	if ($p_mode == 0) {
		// если обычный режим, то вернем id ответсвеннного	
		$GLOBALS["g_owner_id"] = $contactid;
		return $GLOBALS["g_owner_id"];
	} else {
		// иначе вернем json строку с его id и именем
		$result_arr['contactid'] = $contactid;
		$result_arr['contactname'] = json_convert($contactname);
		return $result_arr;	
	}
}

?>