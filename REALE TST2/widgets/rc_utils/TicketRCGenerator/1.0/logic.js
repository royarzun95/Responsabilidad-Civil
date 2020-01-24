RightNow.namespace('Custom.Widgets.rc_utils.TicketRCGenerator');
Custom.Widgets.rc_utils.TicketRCGenerator = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
        this.widget         = this.Y.one(this.baseSelector);
        this.btn_submit     = this.widget.one('#btn_submit');


        this.loadWidgets = window.setInterval((function(_parent) {
            return function() {
                var x = Integer.getInstanceByName('father');
                if (x) {
                  _parent.init();
                  window.clearInterval(_parent.loadWidgets);
                }
      
            };
          })(this), 100);
        },

    init: function () {
        this.father         = Integer.getInstanceByName('father');
        this.plate          = Integer.getInstanceByName('plate');
        this.description    = Integer.getInstanceByName('description');
        this.btn_submit.on('click', this.prepareData, this);
        
      },


    /**
     * Sample widget method.
     */
    prepareData: function() {
        this._data  = {};
        this._data.father       = this.father.input.get('value');
        this._data.plate        = this.plate.input.get('value');
        this._data.description  = this.description.input.get('value');

        this.getSend_incident(this._data);
    },

    /**
     * Makes an AJAX request for `send_incident`.
     */
    getSend_incident: function(params) {
    
        var eventObj = new RightNow.Event.EventObject(this, {
            data: {
                w_id: this.data.info.w_id,
                data: JSON.stringify(params)
            }
            });
        
        RightNow.Ajax.makeRequest(this.data.attrs.send_incident, eventObj.data, {
        successHandler: this.send_incidentCallback,
        scope: this,
        data: eventObj,
        timeout: 60000,
        json: true
        });
    },

    /**
     * Handles the AJAX response for `send_incident`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj` from #getSend_incident
     */
    send_incidentCallback: function(response, originalEventObj) {
        if (!response.success) {
            RightNow.UI.displayBanner(response.message, { type: 'ERROR' });
        } else {
            RightNow.UI.displayBanner('Formulario enviado con éxito.', { type: 'SUCCESS' });
        }
    }
});