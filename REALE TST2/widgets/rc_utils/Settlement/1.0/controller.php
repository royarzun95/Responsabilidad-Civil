<?php
namespace Custom\Widgets\rc_utils;
use RightNow\Connect\v1_3 as RNCPHP;

require_once APPPATH . 'libraries/tcpdf/tcpdf.php';

class INTPDF extends \TCPDF {

  // Encabezado
  public function Header() {
    $this->ImageSVG($file=HTMLROOT . ASSETS_ROOT . '/images/pdf/header.svg', $x=7.2, $y=4.5, $w='187.2', $h='24.8', $link='', $align='', $palign='', $border=0, $fitonpage=false);
  }

  // Pie de página
  public function Footer() {
  }
}
  
/**
 * 
 */
class Settlement extends \RightNow\Libraries\Widget\Base
{
  /**
   * 
   */
  function __construct($attrs)
  {
    parent::__construct($attrs);

    \RightNow\Libraries\AbuseDetection::check();
    
    $this->setAjaxHandlers(array(
      'getPDF_ajax_endpoint' => array(
        'method'      => 'handle_getPDF_ajax_endpoint',
        'clickstream' => 'getPDF_ajax_endpoint',
      )
    ));
  }
  
  /**
   * 
   */
  function getData()
  {
    return parent::getData();
  }
  
  /**
   * 
   */
  function handle_getPDF_ajax_endpoint($params)
  {
    header('Content-Type: application/pdf');

    require_once(APPPATH . 'libraries/tcpdf/tcpdf.php');

    // Nuevo PDF
    $pdf = new INTPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Integer');
    $pdf->SetTitle('Documento Reale Seguros');
    $pdf->SetSubject('Generado en Oracle Service Cloud');
    $pdf->SetKeywords('Reale, Seguros, PDF');

    // Eliminar el header/footer por defecto
    // $pdf->setPrintHeader(false);
    // $pdf->setPrintFooter(false);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

    // Establece el auto salto de página
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Establece el factor de ratio
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Dependencia de idiomas
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
      require_once(dirname(__FILE__) . '/lang/eng.php');
      $pdf->setLanguageArray($l);
    }

    // ---------------------------------------------------------
    // Declaración de variables de valores
    // ---------------------------------------------------------

    // Valores de tabla resumen
    $third_name      = 'Roberto Gómez Bolaño';
    $third_rut       = '21.001.202-0';
    $third_vehicle   = 'Tesla Cybertruck';
    $third_plate     = 'DLPJ-33';
    $policy          = '654065646';
    $item            = '3';
    $coverage        = 'Responsabilidad Civil Daño Emergente - Vehículos Motorizados';
    $sinister_number = '56466';
    $insured_plate   = 'ABCD-66';
    
    // Valores carta
    $insured_name = 'Benjavier Gonzazún Castillo';
    $ammount      = '1.213.654';

    // Información bancaria del tercero
    $third_type_account   = 'Cta. Corriente';
    $third_account_number = '61321321';
    $third_bank           = 'Banco Crédito e Inversiones';

    // Establece la tipografía base
    $pdf->SetFont('helvetica', 'B', 11);

    // Nueva Página
    $pdf->AddPage();

    $pdf->writeHTML('<br>', true, 0, true, 0);
    $pdf->Write(0, 'FINIQUITO DE INDEMNIZACIÓN EN DINERO Y RENUNCIA DE ACCIONES', '', 0, 'C', true, 0, false, false, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);
    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->SetFont('helvetica', '', 11);

    $table_1 = '';
    $table_1 .= '<table>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Tercero Afectado</td>';
    $table_1 .= '    <td colspan="3">: ' . $third_name . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>RUT</td>';
    $table_1 .= '    <td colspan="3">: ' . $third_rut . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Vehículo afectado</td>';
    $table_1 .= '    <td>: ' . $third_vehicle . '</td>';
    $table_1 .= '    <td>Patente:</td>';
    $table_1 .= '    <td>: ' . $third_plate . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Póliza</td>';
    $table_1 .= '    <td>: ' . $policy . '</td>';
    $table_1 .= '    <td>Ítem:</td>';
    $table_1 .= '    <td>: ' . $item . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Cob. Afectada</td>';
    $table_1 .= '    <td colspan="3">: <b>' . $coverage . '</b></td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Número de Siniestro</td>';
    $table_1 .= '    <td colspan="3">: ' . $sinister_number . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '  <tr>';
    $table_1 .= '    <td>Patente Vehículo Asegurado</td>';
    $table_1 .= '    <td colspan="3">: ' . $insured_plate . '</td>';
    $table_1 .= '  </tr>';
    $table_1 .= '<table>';
    $pdf->writeHTML($table_1, true, 0, true, 0);

    $pdf->writeHTML('***********************************************************************', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">Se establece que el tercero afectado antes individualizado, precaviendo un litigio eventual, en su calidad de propietario del Vehículo Afectado ya individualizado. De la liquidación de siniestro efectuada por <b>' . $insured_name . '</b>; declara aceptar de la Compañía Reale Chile Seguros Generales S.A. RUT: 76.743.492-8, la suma única y total de:</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">$' . $ammount . '.- {pesos}</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">La suma anterior representa el monto total de la indemnización por todos los daños ocasionados a su vehículo y pasajeros del mismo, derivados del accidente individualizado bajo en número de siniestro antes indicado, en que su vehículo fuera chocado por el vehículo patente ya indicada asegurado en esta compañía</p>', true, 0, true, 0);
    
    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">El pago total se efectuará al tercero afectado a su cuenta bancaria, vía depósito a  RUT:  a la Cuenta tipo ' . $third_type_account . ', número de cuenta ' . $third_account_number . ' del Banco ' . $third_bank . ' cuyo aviso se enviará al siguiente correo electrónico</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);
    
    $pdf->writeHTML('<p style="text-align:justify;">De no contar con datos bancarios se emitirá vale vista</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);
    
    $pdf->writeHTML('<p style="text-align:justify;">En mérito de lo anterior y sujeto al pago de la suma señalada, el indemnizado declara que cualquier perjuicio directo o indirecto, previsto o imprevisto en su persona o vehículo individualizado y lucro cesante, ha sido totalmente solucionado y renuncia a cualquier acción civil, penal, contravencional o de cualquier otra índole que pudiere haber en contra del asegurado o del propietario del vehículo  y/o del conductor del vehículo asegurado otorgando a las personas señaladas y a Compañía Reale Chile Seguros Generales S.A., el más amplio total e irrevocable finiquito, sin tener ya reclamo alguno que formular</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);
    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">Firma : ________________________________</p>', true, 0, true, 0);

    $pdf->writeHTML('<br>', true, 0, true, 0);
    $pdf->writeHTML('<br>', true, 0, true, 0);

    $pdf->writeHTML('<p style="text-align:justify;">RUT   : ________________________________</p>', true, 0, true, 0);

    echo $pdf->Output('finiquito.pdf', 'S');
  }
}