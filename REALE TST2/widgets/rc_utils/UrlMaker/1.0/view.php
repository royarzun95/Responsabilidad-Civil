<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
  <div id="message_form"></div>

  <div id="rn_ErrorLocation" class="rn_MessageBox rn_ErrorMessage" hidden="hidden" style="display: none;">
    <h2 role="alert">
    Errores
    </h2>
    <div class="messages">
    </div>
  </div>
  <!-- Inicio del formulario -->
  <form id="form_token" method="post">
    <fieldset>
      <div class="form-content">
        <legend>
          Generar URL
        </legend>
        <rn:widget path="custom/input/InputField" id="url_field"      name="url_field"      label_input="Enlace"  value="#rn:php:$this->data['URL_field']#"      display_type="text"   auto_invalid="true" wide="true" disabled="true" />
        <rn:widget path="custom/input/InputField" id="select_day"     name="select_day"     label_input="Días"    value="30"                                     display_type="number" auto_invalid="true" required="true" default_value="30" />
        <rn:widget path="custom/input/InputField" id="select_hours"   name="select_hours"   label_input="Horas"   value="#rn:php:$this->data['select_hours']#"   display_type="number" auto_invalid="true" required="true" default_value="0" />
        <rn:widget path="custom/input/InputField" id="select_minutes" name="select_minutes" label_input="Minutos" value="#rn:php:$this->data['select_minutes']#" display_type="number" auto_invalid="true" required="true" default_value="0" />
      </div>
    </fieldset>
    <fieldset>
      <div class="form-content">
        <legend>
          Informacion URL Generada
        </legend>
        <rn:widget path="custom/input/InputField" id="remaining_time" name="remaining_time" label_input="Fecha de expiración"   value="#rn:php:$this->data['js']['remaining_time']#"  display_type="text" disabled="true" />
        <rn:widget path="custom/input/InputField" id="status"         name="status"         label_input="Estado de ticket"      value="#rn:php:$this->data['js']['status']#"          display_type="text" disabled="true" />
        <rn:widget path="custom/input/InputField" id="ref_number"     name="ref_number"     label_input="Numero de referencia"  value="#rn:php:$this->data['js']['ref_number']#"      display_type="text" disabled="true" />
      </div>
    </fieldset>
    <div class="form-element-wide">
      <div class="rn_FormSubmit">
        <input type="button" id="btn_submit" name="btn_submit" value="Generar Enlace">
      </div>
    </div>
    
  </form>
</div>