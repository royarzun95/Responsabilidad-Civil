<rn:meta title="Generar Tickets RC" template="reale.php" login_required="false" clickstream="rc_fileLoad"/>

<div class="content-body wrapper form">
<? $inc = getUrlParm('inc'); ?>
  <rn:widget path="custom/rc_utils/TicketRCGenerator" incident="#rn:php:$inc#"/>
</div>
<div class="content-body wrapper success" hidden="hidden" style="display: none;">
  <div class="content-title wrapper">
    <h1>Documentos Añadidos con exito</h1>
    <p>Estaremos en contacto con usted.</p>
  </div>
</div>
