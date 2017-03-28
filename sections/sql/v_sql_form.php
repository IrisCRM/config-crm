<table id="sql_section">
<tbody>
<tr><td>

<form name="u_sql_send" method="POST" onsubmit="return false;">

<table width="100%" border="0" align="left" valign="top">
<tbody>

<tr class="info">
<td class="info" colspan=2><?php 
  echo $T->t('Будьте аккуратны, выполняя SQL запросы'); ?>!</td>
</tr>
<tr class="info">
<td class="info" colspan=2><strong><?php 
  echo $T->t('Не выполняйте SQL скрипты дважды, если этого не требуется'); ?>.
  </strong>
</td>
</tr>
<tr class="info">
</td>
</tr>

<tr>
<td colspan="2">
<h2 style="margin: 20px 0px 10px 0px;"><?php 
  echo $T->t('Выполнение запроса'); ?></h2>
</td>
</tr>
<tr>
<td colspan="2"><textarea id="sql" name="sql" value="<?php 
  echo $T->t('Введите текст SQL запроса'); 
  ?>" style="width: 90%; height: 300px;" class="edtText"></textarea></td>
</tr>
<tr>
<tr>
<td colspan="2">
<input name="setuptables" type="button" onclick="irisControllers.objects.u_sqlgrid_area.runSQL();" value="<?php 
  echo $T->t('Выполнить'); ?>" class="button" title="<?php 
  echo $T->t('Нажмите, чтобы выполнить скрипт'); ?>.">
</td>
</tr>
<tr>
<td colspan="2">
<div id="u_sql_sqlresult">
</div>
</td>
</tr>
</tbody>
</table>

</form>

</td>
</tr>
</tbody>
</table>