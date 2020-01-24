RightNow.namespace('Custom.Widgets.rc_utils.UrlMaker');

Custom.Widgets.rc_utils.UrlMaker = RightNow.Widgets.extend({

  /**
   * Constructor
   */
  constructor: function () {

    // Variables
    this.errors_messages = [];
    this.is_valid        = true;

    // Mapeo de elementos del DOM
    this.widget           = this.Y.one(this.baseSelector);
    this.btn_submit       = this.widget.one('#btn_submit');
    this.errors_container = this.widget.one('#rn_ErrorLocation');
    

    // Ejecuta `init` una vez realizada la carga de los widgets de entrada
    this.loadWidgets = window.setInterval((function(_parent) {
      return function() {
          var x = Integer.getInstanceByName('url_field');

          if (x) {
              _parent.init();
              window.clearInterval(_parent.loadWidgets);
          }

      };
    })(this), 100);
  },

  /**
   * 
   */
  init: function() {
  
    // Instancias
    this.url_field      = Integer.getInstanceByName('url_field');
    this.select_month   = Integer.getInstanceByName('select_month');
    this.select_day     = Integer.getInstanceByName('select_day');
    this.select_hours   = Integer.getInstanceByName('select_hours');
    this.select_minutes = Integer.getInstanceByName('select_minutes');
    this.remaining_time = Integer.getInstanceByName('remaining_time');
    this.status         = Integer.getInstanceByName('status');
    this.ref_number     = Integer.getInstanceByName('ref_number');

    // Eventos
    // this.btn_copy.on('click', this.copiarAlPortapapeles, this);
    this.btn_submit.on('click', this.handler_getURL, this);
  },

  /**
   * 
   */
  handler_getURL: function () {

    // Variables
    this.errors          = [];
    this.errors_messages = [];
    this.is_valid        = true;

    RightNow.Event.fire('evt_ValidateInput', this.errors);

    // Errores particulares
    if(parseInt(this.select_day.input.get('value')) === 0 && parseInt(this.select_hours.input.get('value')) === 0 && parseInt(this.select_minutes.input.get('value')) === 0) {
      this.errors.push({
        valid: false,
        name: null,
        instance: null,
        message: 'El tiempo total ingresado es <strong>0</strong>, valide la información.'
      })
    }

    for (var error in this.errors) {
      if (!this.errors[error].valid) {
        this.errors_messages.push(this.errors[error].message);

        this.is_valid = false;
      }
    }

    this.errors_container.hide();
    this.errors_container.one('.messages').setHTML('');

    if (!this.is_valid) {
      this.errors_container.one('.messages').setHTML('<p>' + this.errors_messages.join('</p><p>') + '</p>');
      this.errors_container.show();
      window.scrollTo(this.errors_container.getX(), this.errors_container.getY());
      return false;
    }

    var _data         = {};
        _data.day     = parseInt(this.select_day.input.get('value'));
        _data.hours   = parseInt(this.select_hours.input.get('value'));
        _data.minutes = parseInt(this.select_minutes.input.get('value'));

    this.getURL_ajax_endpoint(_data);
  },

  /**
   * ?
   * 
   * @param {*} params 
   */
  getURL_ajax_endpoint: function (params) {

    this.btn_submit.set('disabled', true);

    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        data: JSON.stringify(params)
      }
    });
    RightNow.Ajax.makeRequest(this.data.attrs.getURL_ajax_endpoint, eventObj.data, {
      successHandler: this.getURL_ajax_endpointCallback,
      scope: this,
      data: eventObj,
      timeout: 60000,
      json: true
    });
  },

  /**
   * ?
   * 
   * @param {*} response 
   * @param {*} originalEventObj 
   */
  getURL_ajax_endpointCallback: function (response, originalEventObj) {
    this.btn_submit.set('disabled', false);

    if (typeof response === 'undefined')
    {
      RightNow.UI.displayBanner('No fue posible generar el token.', { type: 'ERROR' });
      return false;
    }

    if (response.success) 
    {
      this.url_field.input.set('value', response.message.URL);
      dateObj = new Date(response.message.remaining_time * 1000).toLocaleString("es-CL", {timeZone: "UTC"}); 
      this.remaining_time.input.set('value', dateObj);
      this.status.input.set('value', response.message.status);
      this.ref_number.input.set('value', response.message.ref_number);
      if (window.external.Incident) 
      {
        var incident = window.external.Incident;    
        incident.SetCustomFieldByName("c$approve_simulation", true);
      }
      RightNow.UI.displayBanner('URL Generada.', { type: 'SUCCESS' });

      return true;
    }
  },

  /**
   * ?
   */
  copiarAlPortapapeles: function ()
  {
    var aux = document.createElement('input');
    aux.setAttribute('value', this.URL_field.get('value'));

    document.body.appendChild(aux);
    aux.select();
    document.execCommand('copy');
    document.body.removeChild(aux);
  }
});