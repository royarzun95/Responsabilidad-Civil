RightNow.namespace('Custom.Widgets.rc_utils.Settlement');

Custom.Widgets.rc_utils.Settlement = RightNow.Widgets.extend({

  /**
   * Constructor
   */
  constructor: function () {
    // Mapeo de elementos del DOM
    this.widget     = this.Y.one(this.baseSelector);
    this.btn_get_pdf = this.widget.one('#btn_get_pdf');

    this.init();
  },

  /**
   * 
   */
  init: function() {
    // Eventos
    this.btn_get_pdf.on('click', this.handler_getPDF, this);
  },

  /**
   * 
   */
  handler_getPDF: function () {
    console.log('> handler_getPDF');
    
    var _data         = {};
        _data.id     = 321;

    this.getPDF_ajax_endpoint(_data);
  },

  /**
   * ?
   * 
   * @param {*} params 
   */
  getPDF_ajax_endpoint: function (params) {

    this.btn_get_pdf.set('disabled', true);

    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        data: JSON.stringify(params)
      }
    });
    RightNow.Ajax.makeRequest(this.data.attrs.getPDF_ajax_endpoint, eventObj.data, {
      successHandler: this.getPDF_ajax_endpointCallback,
      scope: this,
      data: eventObj,
      timeout: 60000,
      json: false
    });
  },

  /**
   * ?
   * 
   * @param {*} response 
   * @param {*} originalEventObj 
   */
  getPDF_ajax_endpointCallback: function (response, originalEventObj) {
    this.btn_get_pdf.set('disabled', false);

    saveData = (function () {
      var a = document.createElement("a");
      document.body.appendChild(a);
      a.style = "display: none";
      return function (data, fileName) {
          var json = JSON.stringify(data),
              blob = new Blob([json], {type: "octet/stream"}),
              url = window.URL.createObjectURL(blob);
          a.href = url;
          a.download = fileName;
          a.click();
          window.URL.revokeObjectURL(url);
      };
    }());

    saveData(response.responseText, 'finiquito.pdf');

    // window.open("data:application/pdf," + escape(response)); 

    // if (response.success) {
    //   console.log('Se decargó');
      
    //   return true;
    // } else {
    //   console.log('Error en la decarga');
    // }
  }
});