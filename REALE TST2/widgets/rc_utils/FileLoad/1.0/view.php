<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
  <div id="message_form"></div>
  <div id="rn_ErrorLocation" class="rn_MessageBox rn_ErrorMessage" hidden="hidden" style="display: none;">
    <h2 role="alert">#rn:msg:CUSTOM_MSG_MISTAKES#</h2>
    <div class="messages"></div>
  </div>

  <!-- FORMULARIO ETAPA 1 - SOLICITUD DE CARGA DE ARCHIVO  -->
  <? if ($this->data['js']['ticket_status'] == 185 OR $this->data['js']['ticket_status'] == 2) : ?>
    <form id="form_file_load" method="post">

      <div class="content-title wrapper">
        <h1>Ingreso de Documentación Responsabilidad Civil</h1>
        <p>Ingrese la información del formulario.</p>
      </div>

      <fieldset>
        <div class="form-content">
          <legend>Datos Cliente</legend>
          <rn:widget path="custom/input/InputField" id="person_rut"      name="person_rut"      label_input="RUT"                value="#rn:php:$this->data['person_rut']#"      display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_id"       name="person_id"       label_input="ID"                 value="#rn:php:$this->data['person_id']#"       display_type="text"  disabled="true" custom_classes="rn_Hidden" />
          <rn:widget path="custom/input/InputField" id="person_name"     name="person_name"     label_input="Nombre"             value="#rn:php:$this->data['person_name']#"     display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_lastname" name="person_lastname" label_input="Apellido"           value="#rn:php:$this->data['person_lastname']#" display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_mobile"   name="person_mobile"   label_input="Teléfono"           value="#rn:php:$this->data['person_mobile']#"   display_type="text"  required="true" auto_invalid="true" />
          <rn:widget path="custom/input/InputField" id="person_email"    name="person_email"    label_input="Correo Electrónico" value="#rn:php:$this->data['person_email']#"    display_type="email" required="true" auto_invalid="true" />
          <rn:widget path="custom/input/InputField" id="index"           name="index"           display_type="text"              disabled="true" custom_classes="rn_Hidden" />
        </div>
      </fieldset>
      
      <div class="rn_MessageBox rn_WarnMessage">
        <h2>Solicitamos que nos envíe los siguientes archivos</h2>
        <ul>
          <li>Cédula de Identidad</li>
          <li>Licencia de conducir</li>
          <li>Padrón del vehículo</li>
          <li>Set de fotografías como se muestra en archivo adjunto</li>
        </ul>
      </div>

      <fieldset>
        <div class="form-content">
          <legend>Carga de Archivos</legend>
          <rn:widget path="custom/generic/uploadFiles" cc_upload_name="rcUpload" obj_id="#rn:php:$this->data['incident']#" model_name="rcModel" custom_classes="standard hiddenButton" />
        </div>
      </fieldset>

      <fieldset>
        <div class="form-cntent">
          <rn:widget path="custom/input/InputField" id="obj_id" name="obj_id" label_input="ID" value="#rn:php:$this->data['incident']#" display_type="text" required="false" disabled="true" custom_classes="rn_Hidden" />
        </div>
      </fieldset>

      <div class="form-element-wide">
        <div class="rn_FormSubmit">
          <input type="button" id="btn_submit" name="btn_submit" value="Enviar">
        </div>
      </div>

    </form>
  <?php endif; ?>

  <!-- FORMULARIO ETAPA 2 - ACEPTACION DE PROPUESTA IAXIS -->
  <? if ($this->data['js']['ticket_status'] == 178) : ?>
    
    <form id="form_file_load" method="post">

      <div class="content-title wrapper">
        <h1>Formulario Terminos y Finiquitación</h1>
        <p>Ingrese la información del formulario.</p>
      </div>

      <fieldset>

        <div class="form-content">
          <legend>Respuesta por Responsabilidad Civil</legend>

          <div class="rn_MessageBox rn_WarnMessage">
            <ul>
              <? foreach ($this->data["partes_con_valor"] as $clave => $valor) : ?>
                <li>El vehículo requiere <strong><?= $valor ?></strong>.</li>
              <? endforeach; ?>
            </ul>
            por lo que <b>Reale Seguros S.A.</b> le entregará la suma de <strong>$<?= number_format($this->data["final_amount"], 0, ',', '.'); ?>.-</strong>
          </div>

          <div class="form-element-wide">
            <rn:widget path="custom/input/InputField" id="observation" name="observation" label_input="Observaciones" display_type="textarea" wide="true" />
          </div>
          <div class="form-element-wide">
            <rn:widget path="custom/input/InputField" id="obj_id" name="obj_id" label_input="ID" value="#rn:php:$this->data['js']['incident']#" display_type="text" required="false" disabled="true" custom_classes="rn_Hidden"/>
          </div>
        </div>

        <div class="form-element-wide rn_ActionForm">
          <p>¿Acepta la propuesta de indemnización?</p>
          <input type="button" id="btn_acept" name="btn_acept" value="Acepto">
          <input type="button" id="btn_dny" name="btn_dny" value="Rechazo">
        </div>

      <fieldset>

    </form>
  <?php endif; ?>

  <!-- FORMULARIO ETAPA 3 - SOLICITUD DE CARGA DE ARCHIVO FIRMADO ANTE NOTARIO -->
  <? if ($this->data['js']['ticket_status'] == 161) : ?>
    <form id="form_file_load" method="post">
      <div class="content-title wrapper">
        <h1>Documento Notarial</h1>
        <p>Descargue el documento, firmelo ante notario, escaneelo y vuelvalo a subir.</p>
      </div>

      <fieldset>
        <div class="form-cntent">
          <legend>Paso 1.  Completar Datos Personales</legend>
          <rn:widget path="custom/input/InputField" id="person_rut"      name="person_rut"      label_input="RUT"                value="#rn:php:$this->data['person_rut']#"      display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_id"       name="person_id"       label_input="ID"                 value="#rn:php:$this->data['person_id']#"        display_type="text"  disabled="true" custom_classes="rn_Hidden" />
          <rn:widget path="custom/input/InputField" id="person_name"     name="person_name"     label_input="Nombre"             value="#rn:php:$this->data['person_name']#"     display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_lastname" name="person_lastname" label_input="Apellido"           value="#rn:php:$this->data['person_lastname']#" display_type="text"  disabled="true" />
          <rn:widget path="custom/input/InputField" id="person_mobile"   name="person_mobile"   label_input="Teléfono"           value="#rn:php:$this->data['person_mobile']#"   display_type="text"  required="true" auto_invalid="true" />
          <rn:widget path="custom/input/InputField" id="person_email"    name="person_email"    label_input="Correo Electrónico" value="#rn:php:$this->data['person_email']#"    display_type="email" required="true" auto_invalid="true" />
        </div>
      </fieldset>

      <fieldset>
        <div class="form-cntent">
          <legend>Paso 2.  Completar Datos de Transferencias</legend>

          <rn:widget path="custom/input/SelectField" id="compensation_type" name="compensation_type" label_input="Forma de Pago" value="" display_type="select" required="true" />
          <rn:widget path="custom/input/InputField"  id="amount" name="amount" label_input="Monto a indemnizar" value="$ #rn:php:$this->data['js']['net_amount']# .-" display_type="text" required="true" disabled="true"/>
            
          <div id="form_block" name="form_block" hidden>
            <rn:widget path="custom/input/SelectField" id="transfer_selector" name="transfer_selector" label_input="Banco"             value="" display_type="select" required="true" />
            <rn:widget path="custom/input/SelectField" id="account_selector"  name="account_selector"  label_input="Tipo de cuenta"    value="" display_type="select" required="true" />
            <rn:widget path="custom/input/InputField"  id="nro_cta"           name="nro_cta"           label_input="Numero de cuenta"  value="" display_type="text"   disabled="false" />
          </div>
        </div>
      </fieldset>

      <fieldset>
        <div class="form-content">
          <legend>Paso 3.  Descargar Finiquito de Indemnización y Firma Notarial</legend>
          <button type="button" id="downloader" name="downloader">Descargar</button>
        </div>
      </fieldset>

      <fieldset>
        <div class="form-content">
          <legend>Paso 4.  Carga de Archivos Notariados</legend>
          <rn:widget path="custom/generic/uploadFiles" cc_upload_name="rcUpload" obj_id="#rn:php:$this->data['incident']#" model_name="rcModel" custom_classes="standard hiddenButton" />
        </div>
      </fieldset>
      
      <fieldset>
        <div class="form-cntent">
          <rn:widget path="custom/input/InputField" id="obj_id" name="obj_id" label_input="ID" value="#rn:php:$this->data['incident']#" display_type="text" required="false" disabled="true" custom_classes="rn_Hidden" />
        </div>
      </fieldset>

      <div class="form-element-wide">
        <div class="rn_FormSubmit">
          <input type="button" id="btn_submit_normalized" name="btn_submit_normalized" value="Enviar">
        </div>
      </div>

    </form>

  <?php endif; ?>
  <!-- FORMULARIO ETAPA 0 - FORMULARIO EN GESTIÓN -->
  <?if (in_array($this->data['js']['ticket_status'],$this->data['js']['allowed_status']) ): ?>
    <?else:?>
    <h1>Ticket en <strong>gestión</strong></h1>
  <?php endif; ?>
</div>