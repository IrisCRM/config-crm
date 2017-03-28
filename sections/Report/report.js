function redraw_report(p_format) 
{
	if (IsEmptyValue(p_format)) {
    p_format = null;
  }
  var form = document.getElementById('window_filter_report').down();
  RunReport($(form), p_format);
}

function openReport(p_reportid, p_paramname, p_value, p_paramcaption, p_displayvalue)
{
  var form = $(document.getElementById('parameters').down());

  //Установить id открываемого отчёта
  form.setAttribute('reportid', p_reportid);
  
  //Добавить название параметра в атрибут parameters формы
  var parameters = form.getAttribute('parameters');
  var filters = parameters.evalJSON();
  //Учтём случай повторения параметров
  //Если такой параметр уже есть, то его добавлять в массив не будем, а из html удалим
  var norepeat = true;
  for (var i=0; i<filters.length-1; i++) {
    if (filters[i] == p_paramname) {
      norepeat = false;
      var tr = $(form).down('[id="'+p_paramname+'"]').up('tr.form_row');
      if (tr) {
        tr.remove();
      }
    }
  }
  if (norepeat) {
    filters[filters.length] = p_paramname;
  }
  parameters = Object.toJSON(filters);
  form.setAttribute('parameters', parameters);

  //Установить значение параметра (условие - "=")
  var html = '';
  if (IsEmptyValue(p_displayvalue)) {
    html = '<input id="'+p_paramname+'" type="text" elem_type="text" value="'+p_value+'" >';
  }
  else {
    html = '<input id="'+p_paramname+'" type="text" elem_type="lookup" lookup_value="'+p_value+'" original_value = "'+p_displayvalue+'" value="'+p_displayvalue+'" >';
  }
  
  html = '<tr><td>'+p_paramcaption+'</td><td>'+
    '<select filterid="" parameterid="" id="c_'+p_paramname+'" elem_type="select"><option selected value="1">=</option></select>'+
    '</td><td>'+
    html+
    '</td></tr>';
  var tr = $(form).down('tr');
  //Удалим ранее установленный фильтр
  if (form['#'+p_paramname] != null) {
    form['#'+p_paramname].up('tr').remove();
  }
  //Установим новое значение фильтра
	tr.next(tr.length).insert({'after': html});
  //Открыть отчёт
  RunReport(form);
}

function selectreportrow(row) 
{
	row.toggleClassName('selected');
}

jQuery(document).ready(function() {
  jQuery("select[multiple] option[value='']").remove();
  jQuery('select[multiple]').select2();
});
