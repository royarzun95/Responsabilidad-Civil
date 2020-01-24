<rn:meta title="Subir Archivos" template="reale.php" login_required="false" clickstream="rc_fileLoad"/>

<div class="content-body wrapper form">
  <? $token = getUrlParm('t'); ?>
  <rn:widget path="custom/rc_utils/FileLoad" new_token="#rn:php:$token#"/>
</div>
<div class="content-body wrapper success" hidden="hidden" style="display: none;">
  <div class="content-title wrapper">
    <h1>Documentos Añadidos con éxito</h1>
    <p>Estaremos en contacto con usted.</p>
  </div>
</div>
