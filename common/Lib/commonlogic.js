//********************************************************************
// общие функции, использующиеся в клиентской логике
//********************************************************************

// при выборе страны или региона фильтровать регион и город
// используется в карточке компании и контакта
function common_filtercity(p_wnd_id, p_mode) {
  var p_form = $(p_wnd_id).down('form');
  var countryid = c_Common_GetElementValue(p_form.CountryID);
  var regionid = c_Common_GetElementValue(p_form.RegionID);

  if (p_mode == 'c') {
    var r_id = p_form.CityID.getAttribute('filter_where');
    if (r_id != null) {
      c_Common_SetElementValue(p_form.RegionID, {"Value": '', "Caption": ''});
    }
  }
  if (p_mode == 'r') {
    var r_id = p_form.CityID.getAttribute('filter_where');
    if (r_id != null) {
      r_id = r_id.substring(0, 52);
      var r_name = r_id.substring(0, 11);
      if (r_name == "t0.regionid" && r_id != "t0.regionid = '" + regionid + "'") {
        c_Common_SetElementValue(p_form.CityID, {"Value": '', "Caption": ''});
      }
    }
  }
  
  var citywhere = '';
  if (regionid != '') {
    citywhere = "t0.regionid = '"+regionid+"'";
  }

  var regionwhere = '';
  if (countryid != '') {
    regionwhere = "t0.countryid = '"+countryid+"'";
    citywhere += ((citywhere == '') ? "" : " and ") + regionwhere;
  }

  if (regionwhere != '')
    p_form.RegionID.setAttribute('filter_where', regionwhere);
  else
    p_form.RegionID.removeAttribute('filter_where');
  if (citywhere != '')
    p_form.CityID.setAttribute('filter_where', citywhere);
  else
    p_form.CityID.removeAttribute('filter_where');
}

function common_cardIsSaved(p_wnd_id, p_showmessage_flag) {
  var form = $(p_wnd_id).getElementsByTagName("form")[0];
  
  if (p_showmessage_flag == undefined) {
    p_showmessage_flag = 1;
  }
  if (form._mode.value == 'insert') {
    if (p_showmessage_flag == 1) {
      wnd_alert('Необходимо сначала сохранить запись');
    }
    return 0;
  }
  
  if (form._hash.value != GetCardMD5(p_wnd_id)) {
    if (p_showmessage_flag == 1) {
      wnd_alert('Необходимо сначала сохранить запись (ctrl+OK)');
    }
    return 0;
  }
  return 1;
}

/*
  универсальная функция создания дочерней записи (счета, кп и т.д.). Вызывает требуемую функцию на сервере в зависимости от переданных параметров и формирует сообщение на клиенте
  
  p_scripname - имя скрипта
  p_func - функция
  p_rec_id - id записи
  p_section_name - section_name
  p_family - род записи (m,f,s) -> ('', 'а', 'о')
  section_code - код раздела
  p_incl_flag - флаг "содержит товары"
*/
function common_createChildRecord(p_scripname, p_func, p_rec_id, 
    p_section_name, p_family, p_section_code, p_incl_flag, main_section_code) {
  var familystr = '';
  switch (p_family) {
    case 'm':
      familystr = '';
      break;
    case 'f':
      familystr = 'а';
      break;
    case 's':
      familystr = 'о';
      break;
    default:
      familystr = '';
  }
  
  if (p_rec_id == '') {
    wnd_alert("Не указан"+familystr+" "+p_section_name.toLowerCase());
    return;
  }

  if (p_scripname.indexOf('/') == -1 && main_section_code != undefined) {
    var className = p_scripname;
    Transport.request({
      section: main_section_code, 
      'class': className, 
      method: p_func, 
      parameters: {
        id: p_rec_id
      },
      onSuccess: function (transport) {
        var result = transport.responseText.evalJSON().data;
        var number = result.UpdateNumber.Number;
        
        if ((number != '') && (number != null)) {
          var message = "<p>Создан"+familystr+" "+p_section_name.toLowerCase()+" №" + number + ".</p>";
          if (p_incl_flag == 1) {
            message += "<p>"+p_section_name+" содержит товарные позиции, которые не были внесены ранее.</p>";
          }
          Common_ShowCustomWindow(p_section_name+" создан"+familystr, message, {
              "Открыть": "openCard('grid', '"+p_section_code+"', '"+result.UpdateNumber.ID+"', 'grid'); Windows.close(get_window_id(this));",
              "ОК": "Windows.close(get_window_id(this));"
            }, 300, 120);
        }
        else {
          wnd_alert("Ошибка<br/><br/>"+p_section_name+" не создан"+familystr, 300, 100);
        }
      }
    });
  }
  else {
    new Ajax.Request(g_path+p_scripname, {
      parameters: {
        '_func': p_func,
        '_p_id': p_rec_id
      },
      onSuccess: function(transport) {
        var result = transport.responseText.evalJSON();
        var number = result.UpdateNumber.Number;
        
        if ((number != '') && (number != null)) {
          var message = "<p>Создан"+familystr+" "+p_section_name.toLowerCase()+" №" + number + ".</p>";
          if (p_incl_flag == 1) {
            message += "<p>"+p_section_name+" содержит товарные позиции, которые не были внесены ранее.</p>";
          }
          Common_ShowCustomWindow(p_section_name+" создан"+familystr, message, {
              "Открыть": "openCard('grid', '"+p_section_code+"', '"+result.UpdateNumber.ID+"', 'grid'); Windows.close(get_window_id(this));",
              "ОК": "Windows.close(get_window_id(this));"
            }, 300, 120);
        }
        else {
          wnd_alert("Ошибка<br/><br/>"+p_section_name+" не создан"+familystr, 300, 100);
        }
      }
    });
  }
}

function common_getIDFromCont(p_mode, p_cont_id, p_rec_id) {
  var rec_id = '';
  if (p_mode == 'grid') {
    rec_id = getGridSelectedID(p_cont_id);
    if (rec_id == '') {
      //wnd_alert('Нужно выбрать запись');
      return '';
    }
  }
  if (p_mode == 'card') {
    var form = $(p_cont_id).getElementsByTagName("form")[0];
    rec_id = form._id.value;
    if (rec_id == '') {
      //wnd_alert('Нужно сохранить запись');
      return '';
    }    
  }
  if (p_mode == 'id') {
    rec_id = p_rec_id;
  }
  
  return rec_id;
}

// ----- универсальные функции создания дочерних записей -----
// ----- вызываются из соотв. карточки и таблицы записей -----

// заказ -> кп
function common_createProjectOffer(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Project',
    'createOffer', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'КП', 
    's', 
    'Offer', 
    0,
    'Project'
  );
}

// заказ -> договор
function common_createProjectPact(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Project',
    'createPact', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Договор', 
    'm', 
    'Pact', 
    0,
    'Project'
  );
}

// заказ -> счет
function common_createProjectInvoice(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Project',
    'createInvoice', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Счет', 
    'm', 
    'Invoice', 
    1,
    'Project'
  );
}


// кп -> договор
function common_createOfferPact(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Offer',//'/config/sections/Offer/g_offer.php', 
    'createPact',//'CreatePact', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Договор', 
    'm', 
    'Pact', 
    0,
    'Offer'
  );
}

// кп -> счет
function common_createOfferInvoice(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Offer',//'/config/sections/Offer/g_offer.php', 
    'createInvoice',//'CreateInvoice', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Счет', 
    'm', 
    'Invoice', 
    1,
    'Offer'
  );
}

// договор -> счет
function common_createPactInvoice(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Pact',//'/config/sections/Pact/g_pact.php', 
    'createInvoice',//'CreateInvoice', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Счет', 
    'm', 
    'Invoice', 
    1,
    'Pact'
  );
}

// договор -> акт
function common_createPactAct(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Pact',//'/config/sections/Pact/g_pact.php', 
    'createAct',//'CreateAct', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Акт', 
    'm', 
    'Document', 
    0,
    'Pact'
  );
}


// счет -> платеж
function common_createInvoicePayment(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Invoice', 
    'createPayment', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Платеж', 
    'm', 
    'Payment', 
    0,
    'Invoice'
  );
}

// счет -> накладная
function common_createInvoiceFactInvoice(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Invoice', 
    'createFactInvoice', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Накладная', 
    'f', 
    'FactInvoice', 
    1,
    'Invoice'
  );
}

// счет -> акт
function common_createInvoiceAct(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_Invoice', 
    'createAct', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Акт', 
    'm', 
    'Document', 
    0,
    'Invoice'
  );
}


// накладная -> платеж
function common_createFactInvoicePayment(p_mode, p_cont_id, p_rec_id) {
  common_createChildRecord(
    'g_FactInvoice', 
    'createPayment', 
    common_getIDFromCont(p_mode, p_cont_id, p_rec_id), 
    'Платеж', 
    'm', 
    'Payment', 
    0,
    'FactInvoice'
  );
}

// -----------------------------------------------------------
