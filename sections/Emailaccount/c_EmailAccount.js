//********************************************************************
// Раздел "Email account". Карточка.
//********************************************************************

irisControllers.classes.c_EmailAccount = IrisCardController.extend({

  testConnection: function() {
    var self = this;
    Transport.request({
        section: "Email", 
        "class": "g_Email", 
        method: "testConnection",
        parameters: {
            emailAccountId: this.parameter('id')
        },
        onSuccess: function (transport) {
            var data = transport.responseText.evalJSON().data;
            var message = "";

            if (data.isSuccess === false) {
              message = data.message;
            } else {
              message = '<pre style="overflow-y: scroll; height: 400px; text-align: left;">' + JSON.stringify(data, null, 2) + "</pre>";
            }

            wnd_alert(message, 500);
        }
    });
  }

});