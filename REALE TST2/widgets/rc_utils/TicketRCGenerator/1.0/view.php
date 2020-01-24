<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
  <div id="message_form"></div>
  <div id="rn_ErrorLocation" class="rn_MessageBox rn_ErrorMessage" hidden="hidden" style="display: none;">
    <h2 role="alert">#rn:msg:CUSTOM_MSG_MISTAKES#</h2>
    <div class="messages"></div>
  </div>
    <form id="form_file_load" method="post">
      <div class="content-title wrapper">
        <h1>Creación de Tickets Responsabilidad Civil</h1><br>
        <p> </p>
      </div>

      <fieldset>
        <div class="form-cntent">
          <legend>Datos de Ticket</legend>
          <br>
          <rn:widget path="custom/input/InputField" id="father"          name="father"          label_input="Incidente Padre"    value="#rn:php:$this->data['father']#"          display_type="text"  disabled="false" />
          <rn:widget path="custom/input/InputField" id="plate"           name="plate"           label_input="Patente"            value="#rn:php:$this->data['plate']#"           display_type="text"  disabled="false" />
          <rn:widget path="custom/input/InputField" id="description"     name="description"     label_input="Descripción"        value="#rn:php:$this->data['description']#"     display_type="text"  disabled="false" />
        </div>
      </fieldset>

      <div class="form-element-wide">
        <div class="rn_FormSubmit">
          <input type="button" id="btn_submit" name="btn_submit" value="Enviar">
        </div>
      </div>

    </form>
</div>