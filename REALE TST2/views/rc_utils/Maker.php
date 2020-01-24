<rn:meta title="Generador de JWT" template="internal.php" clickstream="RC_JWT_MAKER"/>

<div class="content-body wrapper">
    <? $incidentId = getUrlParm('incident_id');?>
    <rn:widget path="custom/rc_utils/UrlMaker" incident_id="#rn:php:$incidentId#" />
</div>