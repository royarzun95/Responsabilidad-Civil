RightNow.namespace('Custom.Widgets.rc_utils.FileLoad');

Custom.Widgets.rc_utils.FileLoad = RightNow.Widgets.extend({
  /**
   * Constructor
   */
  constructor: function () {
    this.widget               = this.Y.one(this.baseSelector);
    this.errors_container     = this.widget.one('#rn_ErrorLocation');
    this.transfer_container   = this.widget.one('#form_block');
    
    this.content_form         = this.Y.one('.content-body.form');
    this.content_success      = this.Y.one('.content-body.success');

    if(this.data.js.ticket_status == 185 || this.data.js.ticket_status == 2)
    {
      this.btn_submit         = this.widget.one('#btn_submit');
      this.fase               = 1;
    }
    if(this.data.js.ticket_status == 178)
    {
      this.btn_acept          = this.widget.one('#btn_acept');
      this.btn_deny           = this.widget.one('#btn_dny');
      this.fase               = 2;
    }
    if(this.data.js.ticket_status == 161)
    {
      this.btn_sendx          = this.widget.one('#btn_submit_normalized');
      this.btn_download       = this.widget.one('#downloader');
      this.fase               = 3;
    }
    // Ejecuta `init` una vez realizada la carga de los widgets de entrada
    this.loadWidgets = window.setInterval((function(_parent) {
      return function() {
        
          var x = Integer.getInstanceByName('index');
          var y = Integer.getInstanceByName('observation');
          var z = Integer.getInstanceByName('account_selector');
          
          if (y || x || z) {
            _parent.init();
            window.clearInterval(_parent.loadWidgets);
          }

      };
    })(this), 100);
  },

  /**
   * inicializacion de objetos de vista
   */
  init: function () {

    this.obj_id            = Integer.getInstanceByName('obj_id');

    if(this.fase==1){
      this.person_rut      = Integer.getInstanceByName('person_rut');
      this.person_name     = Integer.getInstanceByName('person_name');
      this.person_lastname = Integer.getInstanceByName('person_lastname');
      this.person_mobile   = Integer.getInstanceByName('person_mobile');
      this.person_email    = Integer.getInstanceByName('person_email');
      this.person_id       = Integer.getInstanceByName('person_id');
      this.btn_submit.on('click',this.handle_updateContact      ,this);

    }
    else if (this.fase==2){

      this.observation     = Integer.getInstanceByName('observation');

      this.btn_acept.on('click',this.handle_updateStatusPositive,this);
      this.btn_deny.on( 'click',this.handle_updateStatusNegative,this);
    }
    else if(this.fase==3){

      this.person_rut                   = Integer.getInstanceByName('person_rut');
      this.person_name                  = Integer.getInstanceByName('person_name');
      this.person_lastname              = Integer.getInstanceByName('person_lastname');
      this.person_mobile                = Integer.getInstanceByName('person_mobile');
      this.person_email                 = Integer.getInstanceByName('person_email');
      this.person_id                    = Integer.getInstanceByName('person_id');
      this.selector                     = Integer.getInstanceByName('compensation_type');
      this.form_block                   = Integer.getInstanceByName('form_block');
      this.account_type_selector        = Integer.getInstanceByName('account_selector');
      this.transfer_selector            = Integer.getInstanceByName('transfer_selector');
      this.account_selector             = Integer.getInstanceByName('account_selector');
      this.nro_cta                      = Integer.getInstanceByName('nro_cta');

      Integer.appendOptions(this.transfer_selector,     this.data.js.bancos,            'select', null);
      Integer.appendOptions(this.account_type_selector, this.data.js.bank_account_type, 'select', null);
      Integer.appendOptions(this.selector,              this.data.js.liquidation_type,  'select', null);

      this.selector.input.on(     'change', this.show_form,         this);
      this.btn_sendx.on(          'click',  this.loadFileNormalized,this);
      this.btn_download.on(       'click',  this.downloadFile,      this);

    }
  },
  show_form:function(e){
    if(this.selector.input.get('value')==1){
      this.transfer_container.show();
    }
    else{
      this.transfer_container.hide();
    }
  },
  /**
   * Ajax para actualizar datos del contacto e Incidente
   * 
   * @param {*} e (any)
   */
  handle_updateContact: function (e) {
    e.preventDefault();

    RightNow.Event.fire('evt_GetFileList', this.instanceID);

    if(!this.validate()) return false;
    
    this._data                 = {};
    this._data.person_rut      = this.person_rut.input.get('value');
    this._data.person_name     = this.person_name.input.get('value');
    this._data.person_lastname = this.person_lastname.input.get('value');
    this._data.person_mobile   = this.person_mobile.input.get('value');
    this._data.person_email    = this.person_email.input.get('value');
    this._data.person_id       = parseInt(this.person_id.input.get('value'));
    this._data.incident        = parseInt(this.obj_id.input.get('value'));
    this._data.files           = this.arr_files;

    this.updateContact(this._data);
  },

  downloadFile: function(e){
    var link = document.createElement('a');
    link.href = 'http://reale--tst2.custhelp.com/cc/rcPDF/generate/tptoken/'+this.data.js.tokenized;
    link.download = 'finiquito.pdf';
    link.dispatchEvent(new MouseEvent('click'));
    // var MIME_TYPE = "text/pdf";
    // var blob = new Blob('http://reale--tst2.custhelp.com/cc/rcPDF/generate/tptoken/'+this.data.js.tokenized, {type: MIME_TYPE});
    // window.location.href = window.URL.createObjectURL(blob);
    //window.open('http://reale--tst2.custhelp.com/cc/rcPDF/generate/tptoken/'+this.data.js.tokenized,'_blank');
  },

  dialogLarge: function (title, msg) {
    if(this.is_debugger) console.log('> dialog');
    title = title || 'Alerta';
    RightNow.UI.Dialog.messageDialog(msg, {
      title: title,
      width: '600px'
    });
    return true;
  },
 
  /**
   * Validaciones
   * 
   * @param {*} e 
   */

  validate: function() {
    // Variables
    this.errors          = [];
    this.errors_messages = [];
    this.is_valid        = true;

    RightNow.Event.fire('evt_ValidateInput', this.errors);

    // Errores particulares
    if(!this.arr_files.length) {
      this.errors.push({
        valid: false,
        name: null,
        instance: null,
        message: 'Debe adjuntar archivos.'
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
    return this.is_valid;
  },

  handle_updateStatusPositive:function(params){
    this.btn_acept.set('disabled', true);
    var _data         = {};
    _data.observation = this.observation.input.get('value');
    _data.incident_id = this.obj_id.input.get('value');
    _data.aproved     = true;
    this.dialogLarge('Enhorabuena','Su respuesta ha sido ingresada y será administrada por nuestros ejecutivos');
    this.updateStatus(_data);
    this.btn_acept.set('disabled', false);
  },

  handle_updateStatusNegative:function(params){
    this.btn_deny.set('disabled', true);
    
    this.btn_acept.set('disabled',true);
    if(this.observation.input.get('value')==""||this.observation.input.get('value')==null||this.observation.input.get('value')==undefined){
      this.dialogLarge("Alerta",'<h1> Debe ingresar una observación para el rechazo de propuesta de indeminizacion</h1>');    
    }
    else{
      console.log("hola");
      var _data={};
      _data.observation = this.observation.input.get('value');
      _data.incident_id = this.obj_id.input.get('value');
      _data.aproved     = false;
      this.dialogLarge('Enhorabuena','Su respuesta ha sido ingresada y será administrada por nuestros ejecutivos');
      this.updateStatus(_data);
    }
    this.btn_deny.set('disabled', false);
    this.btn_acept.set('disabled',false);
  },

  loadFileNormalized:function(e){
    // this.btn_sendx.set('disabled',true);
    e.preventDefault();
    RightNow.Event.fire('evt_GetFileList', this.instanceID);
    if(!this.validate()) return false;
    _data                       = {};
    _data.person_rut            = this.person_rut.input.get('value');
    _data.person_name           = this.person_name.input.get('value');
    _data.person_lastname       = this.person_lastname.input.get('value');
    _data.person_mobile         = this.person_mobile.input.get('value');
    _data.person_email          = this.person_email.input.get('value');
    _data.person_id             = parseInt(this.person_id.input.get('value'));
    _data.incident              = parseInt(this.obj_id.input.get('value'));
    _data.files                 = this.arr_files;
    _data.compensation_type      = this.selector.input.get('value');
    
    if (_data.compensation_type  == 1)
    {
      _data.transfer_selector     = this.transfer_selector.input.get('value');  // tipo de Pago
      _data.account_selector      = this.account_selector.input.get('value');
      _data.nro_cta               = this.nro_cta.input.get('value');
    }
    else if (_data.compensation_type == 0)
    {
      this.dialogLarge('Error','Debe seleccionar una tipo de compensación');
      this.btn_sendx.set('disabled',false);
    }
    this.sendResponseOfNormalizedDocs(_data);
  },

  sendResponseOfNormalizedDocs: function(params){
    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        data: JSON.stringify(params)
      }
    });

    RightNow.Ajax.makeRequest(this.data.attrs.update_normalized_docs, eventObj.data, {
      successHandler: this.default_ajax_endpointCallback,
      scope: this,
      data: eventObj,
      timeout: 60000,
      json: true
    });
  },

  updateStatus: function(params){
    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        data: JSON.stringify(params)
      }
    });
    RightNow.Ajax.makeRequest(this.data.attrs.update_status_ajax, eventObj.data, {
      successHandler: this.default_ajax_endpointCallback,
      scope: this,
      data: eventObj,
      timeout: 60000,
      json: true
    });
  },
  /**
   * Envia datos de archivo y cambios de contacto a controller
   * 
   * @param {*} params 
   */
  updateContact: function (params) {
    this.btn_submit.set('disabled', true);
    var eventObj = new RightNow.Event.EventObject(this, {
      data: {
        w_id: this.data.info.w_id,
        data: JSON.stringify(params)
      }
    });

    RightNow.Ajax.makeRequest(this.data.attrs.default_ajax_endpoint, eventObj.data, {
      successHandler: this.default_ajax_endpointCallback,
      scope: this,
      data: eventObj,
      timeout: 60000,
      json: true
    });
  },

  /**
   * Establece el ID de los adjunto en el parámetro de creación
   * 
   * @param {*} attachments 
   */
  setAttachments: function (attachments) {
    this.arr_files = attachments;
    return attachments;
  },

  /**
   * Receptor de respuesta desde controller
   * evento final de captura de archivos y datos
   * 
   * @param {*} response 
   * @param {*} originalEventObj 
   */
  default_ajax_endpointCallback: function (response, originalEventObj) {
    if (!response.success) {
      RightNow.UI.displayBanner(response.message, { type: 'ERROR' });
    } else {
      RightNow.UI.displayBanner('Formulario enviado con éxito.', { type: 'SUCCESS' });
      this.content_form.hide();
      this.content_success.show();
    }
    if(this.data.js.ticket_status==107){
      this.btn_submit.set('disabled', false);
    }else if(this.data.js.ticket_status==178){
      this.btn_deny.set('disabled', false);
      this.btn_acept.set('disabled',false);
    }
  }
});