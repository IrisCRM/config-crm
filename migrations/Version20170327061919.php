<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Перенос печатной формы УПД из файлов в БД (printformtext)
 */
class Version20170327061919 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "update iris_printform set printform_file = null, printform_filename = null, istextuse = 1, printformtext = :text where id = :printformId;",
            array(
                ":text" => $this->getPrintformText(),
                ":printformId" => '25327a26-bc4d-be26-39ab-48ffc32c8360',
            )
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            "update iris_printform set printform_file = :file, printform_filename = :filename, istextuse = 0, printformtext = null where id = :printformId;",
            array(
                ":file" => '6916ae16-cdd3-49d8-4f29-fb96614b6ba4',
                ":filename" => 'UPD.html',
                ":printformId" => '25327a26-bc4d-be26-39ab-48ffc32c8360',
            )
        );
    }

    private function getPrintformText()
    {
        return <<<EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Счет-фактура</title>
	<style type="text/css">
		@page { size: landscape; margin-right: 1cm; margin-top: 0.75cm }
		p { margin-bottom: 0.25cm; direction: ltr; font-family: "Arial", serif; font-size: 12pt; color: #000000; widows: 2; orphans: 2 }
		p.western { margin: 0; line-height: 100%; so-language: ru-RU }
		p.sign { margin: 0; so-language: ru-RU }
		.underline { text-decoration: underline;}
		td.line { border-bottom: 1pt solid #000;}
		tr.data font { font-size: 8pt;}
		tr.total p { font-weight: bold;}
		.right { text-align: right; padding-right: 0.15cm;}
	</style>
</head>
<body lang="ru-RU" text="#000000" link="#0000ff" dir="ltr">
<table width="1076" cellpadding="7" cellspacing="0">
	<colgroup>
		<col width="22">
	</colgroup>
	<colgroup>
		<col width="2">
	</colgroup>
	<colgroup>
		<col width="10">
	</colgroup>
	<colgroup>
		<col width="24">
	</colgroup>
	<colgroup>
		<col width="23">
		<col width="1">
		<col width="3">
		<col width="7">
		<col width="59">
		<col width="16">
		<col width="4357">
		<col width="4356">
		<col width="23">
	</colgroup>
	<colgroup>
		<col width="44">
		<col width="44">
	</colgroup>
	<colgroup>
		<col width="20">
		<col width="21">
		<col width="4356">
	</colgroup>
	<colgroup>
		<col width="4362">
	</colgroup>
	<colgroup>
		<col width="20">
		<col width="33">
	</colgroup>
	<colgroup>
		<col width="8">
		<col width="10">
	</colgroup>
	<colgroup>
		<col width="21">
		<col width="4363">
	</colgroup>
	<colgroup>
		<col width="4362">
		<col width="4365">
		<col width="14">
		<col width="21">
	</colgroup>
	<colgroup>
		<col width="62">
		<col width="4360">
		<col width="1">
	</colgroup>
	<colgroup>
		<col width="22">
	</colgroup>
	<colgroup>
		<col width="54">
	</colgroup>
	<colgroup>
		<col width="35">
		<col width="23">
	</colgroup>
	<tbody>
		<tr valign="top">
			<td rowspan="2" colspan="4" width="100" height="22" style="border: none; padding: 0cm">
				<p class="western" style="margin-bottom: 0cm"><font face="Arial, sans-serif"><font size="2" style="font-size: 10pt">Универсальный
				передаточный<br>документ  </font></font>
				</p>
				<p class="western"><br>
				</p>
			</td>
			<td colspan="16" width="446" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" style="margin-bottom: 0cm">
				<font face="Arial, sans-serif"><font size="2">Счет-фактура
				№ <u id="docnumber">[#Номер#]</u> от <u id="docdate">{ДатаСтрокой([#Дата#])}</u> (1)</font></font>
				</p>
				<p class="western" style="margin-right: -0.12cm"><font face="Arial, sans-serif"><font size="2" style="font-size: 10pt">Исправление
				№     __________ от ___________ (1а)</font></font></p>
			</td>
			<td colspan="16" width="488" style="border: none; padding: 0cm">
				<p class="western" align="right" style="margin-bottom: 0cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Приложение № 1</font></font></p>
				<p class="western" align="right" style="margin-bottom: 0cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">к
				постановлению Правительства Российской
				Федерации</font></font></p>
				<p class="western" align="right"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">от 26 декабря 2011 г. № 1137</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="bottom" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><b>Продавец</b></font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Наши реквизиты#.#Полное название#] ([#Счет#.#Наши реквизиты#.#Название#])</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(2)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="4" width="100" height="1" valign="top" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Адрес</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Наши реквизиты#.#Юр. адрес#]</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(2а)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="2" width="38" valign="top" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Статус:
				</font></font>
				</p>
			</td>
			<td width="10" valign="top" style="border-top: 1.50pt solid #000000; border-bottom: 1.50pt solid #000000; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td width="24" valign="top" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">ИНН/КПП
				продавца</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Наши реквизиты#.#ИНН#]/[#Счет#.#Наши реквизиты#.#КПП#]</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(2б)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="4" width="100" height="1" valign="top" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Грузоотправитель
				и его адрес</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"> [#Счет#.#Наши реквизиты#.#Название#], [#Счет#.#Наши реквизиты#.#Юр. адрес#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(3)</font></font></p>
			</td>
		</tr>
		<tr>
			<td rowspan="7" colspan="4" width="100" height="1" valign="top" style="border: none; padding: 0cm">
				<p class=""><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">1
				– счет-фактура и передаточный  документ
				(акт)<br>2 – передаточный  документ
				(акт)</font></font></p>
			</td>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Грузополучатель
				и его адрес</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Реквизиты клиента#.#Название#], [#Счет#.#Реквизиты клиента#.#Юр. адрес#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(4)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">К
				платежно-расчетному документу</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">№ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; от</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(5)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><b>Покупатель</b></font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Реквизиты клиента#.#Название#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(6)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Адрес</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Реквизиты клиента#.#Юр. адрес#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(6а)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">ИНН/КПП
				покупателя</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Счет#.#Реквизиты клиента#.#ИНН#]/[#Счет#.#Реквизиты клиента#.#КПП#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(6б)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="182" valign="top" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Валюта:
				наименование, код</font></font></p>
			</td>
			<td colspan="24" width="715" valign="bottom" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[#Валюта#]</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">(7)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="32" width="182" valign="top" style="border-top: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm"><font size="1"><br></font>
			</td>
		</tr>
	</tbody>
	<tbody class="header">
		<tr>
			<td rowspan="2" width="22" height="21" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center" style="font-size: 8pt">№ <font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">п/п</font></font></p>
			</td>
			<td rowspan="2" colspan="3" width="64" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Код
				 товара/ работ, услуг</font></font></p>
			</td>
			<td rowspan="2" colspan="7" width="181" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Наименование
				товара (описание выполненных работ,
				оказанных услуг), имущественного
				права</font></font></p>
			</td>
			<td colspan="3" width="82" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Единица
				измерения</font></font></p>
			</td>
			<td rowspan="2" width="44" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Количе
				ство (объем)</font></font></p>
			</td>
			<td rowspan="2" colspan="3" width="56" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center" style="margin-right: -0.03cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Цена
				(тариф) за единицу измерения</font></font></p>
			</td>
			<td rowspan="2" colspan="3" width="73" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Стоимость
				товаров (работ, услуг), имущественных
				прав без налога – всего</font></font></p>
			</td>
			<td rowspan="2" colspan="2" width="32" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">В
				том числе сумма акциза</font></font></p>
			</td>
			<td rowspan="2" colspan="2" width="29" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Нало
				говая ставка</font></font></p>
			</td>
			<td rowspan="2" colspan="4" width="66" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Сумма
				налога, предъявляемая покупателю</font></font></p>
			</td>
			<td rowspan="2" colspan="3" width="83" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Стоимость
				товаров (работ, услуг), имущественных
				прав с налогом - всего</font></font></p>
			</td>
			<td colspan="2" width="91" style="border-top: 1.00pt solid #000000; border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Страна
				происхождения товара</font></font></p>
			</td>
			<td rowspan="2" colspan="2" width="72" style="border: 1.00pt solid #000000; padding: 0cm 0.19cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Номер
				таможенной декларации</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="2" width="24" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">код</font></font></p>
			</td>
			<td width="44" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">услов
				ное обозначение (национальное)</font></font></p>
			</td>
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Цифро
				вой код</font></font></p>
			</td>
			<td width="54" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Краткое
				наиме нование</font></font></p>
			</td>
		</tr>
		<tr>
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">А</font></font></p>
			</td>
			<td colspan="3" width="64" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Б</font></font></p>
			</td>
			<td colspan="7" width="181" style="border-bottom: 1.00pt solid #000000; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">1</font></font></p>
			</td>
			<td colspan="2" width="24" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">2</font></font></p>
			</td>
			<td width="44" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">2а</font></font></p>
			</td>
			<td width="44" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">3</font></font></p>
			</td>
			<td colspan="3" width="56" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">4</font></font></p>
			</td>
			<td colspan="3" width="73" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">5</font></font></p>
			</td>
			<td colspan="2" width="32" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">6</font></font></p>
			</td>
			<td colspan="2" width="29" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">7</font></font></p>
			</td>
			<td colspan="4" width="66" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">8</font></font></p>
			</td>
			<td colspan="3" width="83" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">9</font></font></p>
			</td>
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">10</font></font></p>
			</td>
			<td width="54" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">10а</font></font></p>
			</td>
			<td colspan="2" width="72" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: 1.00pt solid #000000; padding: 0cm 0.19cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">11</font></font></p>
			</td>
		</tr>
	</tbody>
	<tbody>
		<tr valign="top" class="data row">
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font>[#Продукты#.#Номер#]</font></p>
			</td>
			<td colspan="3" width="64" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="7" width="181" style="border-bottom: 1.00pt solid #000000; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font>[#Продукты#.#Продукт#]</font></p>
			</td>
			<td colspan="2" width="24" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western">&ndash;</p>
			</td>
			<td width="44" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font>[#Продукты#.#Единица#]</font></p>
			</td>
			<td width="44" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>[#Продукты#.#Количество#]</font></p>
			</td>
			<td colspan="3" width="56" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное({Вычесть([#Продукты#.#Цена#], {Делить({Умножить([#Продукты#.#Цена#], [#Продукты#.#Накладная#.#НДС, %#])}, {Сложить(100, [#Продукты#.#Накладная#.#НДС, %#])})})})}</font></p>
			</td>
			<td colspan="3" width="73" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное({Вычесть([#Продукты#.#Сумма#], {Делить({Умножить([#Продукты#.#Сумма#], [#Продукты#.#Накладная#.#НДС, %#])}, {Сложить(100, [#Продукты#.#Накладная#.#НДС, %#])})})})}</font></p>
			</td>
			<td colspan="2" width="32" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western">&ndash;
				</p>
			</td>
			<td colspan="2" width="29" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное([#Продукты#.#Накладная#.#НДС, %#])}</font></p>
			</td>
			<td colspan="4" width="66" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное({Делить({Умножить([#Продукты#.#Сумма#], [#Продукты#.#Накладная#.#НДС, %#])}, {Сложить(100, [#Продукты#.#Накладная#.#НДС, %#])})})}</font></p>
			</td>
			<td colspan="3" width="83" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное([#Продукты#.#Сумма#])}</font></p>
			</td>
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western">&ndash;
				</p>
			</td>
			<td width="54" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font>[#Продукты#.#Продукт#.#Производитель#.#Страна#]</font></p>
			</td>
			<td colspan="2" width="72" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: 1.00pt solid #000000; padding-top: 0cm; padding: 0cm 0.19cm">
				<p class="western"><br>
				</p>
			</td>
		</tr>
	</tbody>
	<tbody>
		<tr valign="top" class="data total">
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="3" width="64" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="14" width="405" style="border-bottom: 1.00pt solid #000000; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p lang="en-US" class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><b>Всего к оплате</b></font></font></p>
			</td>
			<td colspan="3" width="73" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="4" width="75" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Х</font></font></p>
			</td>
			<td colspan="4" width="66" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное({Делить({Умножить([#Сумма (с НДС)#], [#НДС, %#])}, {Сложить(100, [#НДС, %#])})})}</font></p>
			</td>
			<td colspan="3" width="83" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western right"><font>{Дробное([#Сумма (с НДС)#])}</font></p>
			</td>
			<td width="22" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td width="54" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="2" width="72" style="border-bottom: 1.00pt solid #000000; border-left: 1.00pt solid #000000; border-right: 1.00pt solid #000000; padding: 0cm 0.19cm">
				<p class="western"><br>
				</p>
			</td>
		</tr>
	</tbody>
	<tbody>
		<tr valign="top" id="first-signature-line">
			<td colspan="4" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="7" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
			</td>
			<td colspan="2" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="1" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="6" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="8" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="3" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
			<td colspan="5" style="border-top: 1.00pt solid #000000; border-bottom: none; border-left: none; border-right: none; padding: 0cm">
			</td>
		</tr>
		<tr>
			<td rowspan="4" colspan="4" valign="top" style="border: none; padding: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">Документ
				составлен на ______ листах</font></p>
			</td>
			<td colspan="7" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">Руководитель
				организации <br>или иное уполномоченное лицо</font></p>
			</td>
			<td colspan="2" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="1" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="6" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">[#Реквизиты клиента#.#Руководитель#]</font></p>
			</td>
			<td colspan="2" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="4" style="border: none; padding: 0cm">
				<p class="western" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">Главный
				бухгалтер или иное уполномоченное
				лицо</font></p>
			</td>
			<td colspan="4" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="1" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="5" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">[#Реквизиты клиента#.#Главный бухгалтер#]</font></p>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="7" width="181" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="sign" align="center">
				</p>
			</td>
			<td colspan="2" width="82" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="1" width="166" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><br></p>
			</td>
			<td colspan="6" width="154" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о.)</font></font></p>
			</td>
			<td colspan="6" width="166" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><br>
				</p>
			</td>
			<td colspan="4" width="103" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="1" width="166" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><br></p>
			</td>
			<td colspan="5" width="192" style="border: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о.)</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="7" width="181" style="border-top: none; border-bottom: none; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">Индивидуальный предприниматель</font></p>
			</td>
			<td colspan="2" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="1" valign="bottom" style="border: none; padding: 0cm">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="6" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
			<td colspan="4" style="border: none; padding: 0cm">
				<p class="western" style="margin-right: -0.19cm"><br>
				</p>
			</td>
			<td colspan="12" valign="bottom" style="padding: 0cm" class="line">
				<p class="western" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 8pt">&nbsp;</font></p>
			</td>
		</tr>
		<tr valign="top">
			<td colspan="7" width="181" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: 2.25pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
			<td colspan="2" width="82" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: none; border-right: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 6pt">(подпись)</font></p>
			</td>
			<td colspan="1" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: none; border-right: none; padding: 0cm">&nbsp;
			</td>
			<td colspan="6" width="154" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: none; border-right: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 6pt">(ф.и.о.)</font></p>
			</td>
			<td colspan="2" width="55" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: none; border-right: none; padding: 0cm">&nbsp;</td>
			<td colspan="14" width="420" style="border-top: none; border-bottom: 2.25pt solid #000000; border-left: none; border-right: none; padding: 0cm">
				<p class="sign" align="center" style="margin-right: -0.19cm"><font size="1" style="font-size: 6pt">(реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</font></p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="10" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Основание
				передачи (сдачи) / получения (приемки)</font></font></p>
			</td>
			<td colspan="25" style="padding: 0cm" class="line">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">Договор [#Договор#.#Номер#] от {ДатаСтрокой([#Договор#.#Дата документа#])}</span></font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p lang="en-US" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[8]</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="10" width="263" valign="bottom" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="25" width="748" valign="top" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(договор;
				доверенность и др.)</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="10" width="190" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Данные
				о транспортировке и грузе</font></font></p>
			</td>
			<td colspan="25" width="821" style="padding: 0cm" class="line">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">&nbsp;</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p lang="en-US" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[9]</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="10" width="190" valign="bottom" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
			<td colspan="25" width="821" valign="top" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(транспортная
				накладная, поручение экспедитору,
				экспедиторская / складская расписка
				и др. / масса нетто/ брутто груза, если
				не приведены ссылки на транспортные
				документы, содержащие эти сведения)</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="western"><br>
				</p>
			</td>
		</tr>

		<tr valign="bottom">
			<td colspan="19" width="526" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Товар
				(груз) передал / услуги, результаты
				работ, права сдал</font></font></p>
			</td>
			<td colspan="17" width="522" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Товар
				(груз) получил / услуги, результаты
				работ, права принял      </font></font>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="5" width="137" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">_______________________</font></font></p>
			</td>
			<td colspan="5" width="142" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">______________________</font></font></p>
			</td>
			<td colspan="6" width="176" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">____________________________</font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">[10]</span></font></font></p>
			</td>
			<td colspan="7" width="162" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">___________________________</font></font></p>
			</td>
			<td colspan="4" width="135" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">______________________</font></font></p>
			</td>
			<td colspan="5" width="160" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">__________________________</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">[15]</span></font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="5" width="137" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">(</span></font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">должность</font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">)</span></font></font></p>
			</td>
			<td colspan="5" width="142" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="6" width="176" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о)</font></font></p>
			</td>
			<td colspan="3" width="29" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
			<td colspan="7" width="162" valign="top" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">(должность)</span></font></font></p>
			</td>
			<td colspan="4" width="135" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="5" width="160" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о)</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="9" width="169" style="border: none; padding: 0cm">
				<p lang="en-US" class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Дата отгрузки, передачи (сдачи)</font></font></p>
			</td>
			<td colspan="7" width="300" style="border: none; padding: 0cm">
				<p class="western" align="center"><font size="1" style="font-size: 8pt">«
				____» ________________________ 20 ____ г.</font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><font size="1" style="font-size: 8pt">[11]</font></p>
			</td>
			<td colspan="7" width="162" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p lang="en-US" class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Дата получения (приемки)</font></font></p>
			</td>
			<td colspan="9" width="309" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">«
				____» ________________________ 20 ____ г.</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[16]</font></font></p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">Иные
				сведения об отгрузке, передаче</font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">Иные
				сведения о получении, приемке</font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western"><font size="1" style="font-size: 8pt">_________________________________________________________________________________</font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><font size="1" style="font-size: 8pt">[12]</font></p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">_________________________________________________________________________________</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p lang="en-US" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[17]</font></font></p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ссылки
				на неотъемлемые приложения, сопутствующие
				документы, иные документы и т.п.)</font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(информация
				о наличии/отсутствии претензии; ссылки
				на неотъемлемые приложения, и другие
				 документы и т.п.)</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
		</tr>

		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Ответственный
				за правильность оформления факта
				хозяйственной жизни</font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Ответственный
				за правильность оформления факта
				хозяйственной жизни</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
		</tr>
		
		<tr valign="bottom">
			<td colspan="5" width="137" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">_______________________</font></font></p>
			</td>
			<td colspan="5" width="142" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">______________________</font></font></p>
			</td>
			<td colspan="6" width="176" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">_____________________________</font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">[13]</span></font></font></p>
			</td>
			<td colspan="7" width="162" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">___________________________</font></font></p>
			</td>
			<td colspan="4" width="135" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">______________________</font></font></p>
			</td>
			<td colspan="5" width="160" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">__________________________</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p lang="en-US" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[18]</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="5" width="137" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">(</span></font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">должность</font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">)</span></font></font></p>
			</td>
			<td colspan="5" width="142" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="6" width="176" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о)</font></font></p>
			</td>
			<td colspan="3" width="29" valign="bottom" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
			<td colspan="7" width="162" valign="top" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">(</span></font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">должность</font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt"><span lang="en-US">)</span></font></font></p>
			</td>
			<td colspan="4" width="135" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(подпись)</font></font></p>
			</td>
			<td colspan="5" width="160" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(ф.и.о)</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Наименование
				экономического субъекта – составителя
				документа (в т.ч. комиссионера / агента)</font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">Наименование
				экономического субъекта – составителя
				документа</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom">
			<td colspan="16" width="483" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">____________________________________________________________</span></font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">____</font></font><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">_________________</span></font></font></p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt"><span lang="en-US">[14]</span></font></font></p>
			</td>
			<td colspan="16" width="485" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">_________________________________________________________________________________</font></font></p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p lang="en-US" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">[19]</font></font></p>
			</td>
		</tr>
		<tr>
			<td colspan="16" width="483" valign="top" style="border: none; padding: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(может
				не заполняться при проставлении
				печати в М.П., может быть указан ИНН /
				КПП)</font></font></p>
			</td>
			<td colspan="3" width="29" valign="bottom" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
			<td colspan="16" width="485" valign="top" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p class="sign" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 6pt">(может
				не заполняться при проставлении
				печати в М.П., может быть указан ИНН /
				КПП)</font></font></p>
			</td>
			<td width="23" valign="bottom" style="border: none; padding: 0cm">
				<p class="sign" align="center"><br>
				</p>
			</td>
		</tr>
		<tr valign="bottom" id="last-signature-line">
			<td colspan="6" width="151" style="border: none; padding: 0cm">
				<p class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">М.П.</font></font></p>
			</td>
			<td colspan="10" width="318" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="3" width="29" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td colspan="8" width="172" style="border-top: none; border-bottom: none; border-left: 1.50pt solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0cm; padding-left: 0.19cm; padding-right: 0cm">
				<p lang="ru-RU" class="western" align="center"><font face="Arial, sans-serif"><font size="1" style="font-size: 8pt">М.П.</font></font></p>
			</td>
			<td colspan="8" width="299" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
			<td width="23" style="border: none; padding: 0cm">
				<p class="western" align="center"><br>
				</p>
			</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript" src="../../core/engine/js/jquery/jquery-1.10.2.js"> </script>
<script type="text/javascript">
	function insertBreak(protoItem, listNumber, header) {
		return protoItem.before(
			'<tr style="padding: 0; margin: 0; page-break-before: always;">' +
				'<td rowspan="1" colspan="20" style="padding: 0; margin: 0;">' + 
					'<div style="padding: 0; margin: 0;"><p class="western"><font face="Arial, sans-serif"><font style="font-size: 10pt" size="2">Универсальный передаточный документ № ' + $('#docnumber').text() + ' от ' + $('#docdate').text() + '</font></font></p></div>' +
				'</td>' +
				'<td rowspan="1" colspan="16" style="padding: 0; margin: 0;" align="right">' + 
					'<div style="padding: 0; margin: 0;"><p class="western"><font face="Arial, sans-serif"><font style="font-size: 10pt" size="2">Лист ' + listNumber + '</font></font></p></div>' +
				'</td>' +
			'</tr>' + 
			header).position().top;
	}
	var pageHeight = 600;
	var listNumber = 1;
	var top1 = 0;
	var header = $('.header');
	$('.row').each(function(i, item) {
		var protoItem = $(item);
		if ((protoItem.position().top - top1) % pageHeight + protoItem.height() > pageHeight) {
			listNumber++;
			top1 = insertBreak(protoItem, listNumber, header.html());
		}
	});
	if (($('#first-signature-line').position().top - top1) % pageHeight > 
		($('#last-signature-line').position().top - top1) % pageHeight) {
		listNumber++;
		insertBreak($('.row').last(), listNumber, header.html());
	}
</script>
</body>
</html>
EOF;
    }
}
