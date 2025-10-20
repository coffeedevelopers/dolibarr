<?php
// Definiciones iniciales para el comportamiento del script
if (!defined("NOREQUIRESOC")) {
    define("NOREQUIRESOC", "1");
}
if (!defined("NOCSRFCHECK")) {
    define("NOCSRFCHECK", 1);
}
if (!defined("NOTOKENRENEWAL")) {
    define("NOTOKENRENEWAL", 1);
}
if (!defined("NOLOGIN")) {
    define("NOLOGIN", 1);
}
if (!defined("NOREQUIREMENU")) {
    define("NOREQUIREMENU", 1);
}
if (!defined("NOREQUIREHTML")) {
    define("NOREQUIREHTML", 1);
}
if (!defined("NOREQUIREAJAX")) {
    define("NOREQUIREAJAX", "1");
}

// Intentar incluir el archivo main.inc.php desde diferentes ubicaciones
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @(include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php");
}

$tmp = empty($_SERVER["SCRIPT_FILENAME"]) ? '' : $_SERVER["SCRIPT_FILENAME"];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;

while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}

if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1) . "/main.inc.php")) {
    $res = @(include substr($tmp, 0, $i + 1) . "/main.inc.php");
}

if (!$res && $i > 0 && file_exists(substr($tmp, 0, $i + 1) . "/../main.inc.php")) {
    $res = @(include substr($tmp, 0, $i + 1) . "/../main.inc.php");
}

if (!$res && file_exists("../../../main.inc.php")) {
    $res = @(include "../../../main.inc.php");
}

if (!$res && file_exists("../../../../main.inc.php")) {
    $res = @(include "../../../../main.inc.php");
}

if (!$res) {
    die("Include of main fails");
}

// Configuración de las cabeceras HTTP
header("Content-Type: application/javascript");
if (empty($dolibarr_nocache)) {
    header("Cache-Control: max-age=3600, public, must-revalidate");
} else {
    header("Cache-Control: no-cache");
}
?>
/* Javascript library of module AFIPWS */
function AddbyCUIT() {
    result = new Array();
    var idprof1 = $("#idprof1").val();
    $.ajax({
        type: 'post',
        url: '<?php echo DOL_MAIN_URL_ROOT . "/afipws/societe/addByCUIT.php"; ?>',
        data: {idPersona: idprof1},
        beforeSend: function() {
            $('#idprof1').after('<img id="hourglasscuit" src="<?php echo DOL_URL_ROOT . "/theme/" . $conf->theme . "/img/working.gif"; ?>">');
        },
        success: function (response) {
            $("#hourglasscuit").remove();
            try {
                result = JSON.parse(response);
                if (result.error) {
                    // handle the error
                    $.jnotify(result.error.msg, 'error', {timeout: 0});
                    return;
                }
            } catch(e) {
                return;
            }
            $('#name').val(result['nombre']);
            $('#address').val(result['domicilio'][0]['direccion']);
            $('#zipcode').val(result['domicilio'][0]['codPostal']);
            $('#town').val(result['domicilio'][0]['localidad']);
            $('#typent_id').val(result['impuesto']);
            $('#phone').val(result['telefono']);
            $('#selectcountry_id').val('Argentina');
            $('#selectcountry_id').trigger('change');
            $('#state_id').val(result['domicilio'][0]['idProvincia']);
            $.jnotify(result['estadoClave'], 'info', {timeout: 0});
            //var arrayState = $('.state_id').select2('data')
            //$('#select2-state_id-container').val(result['domicilio'][0]['descripcionProvincia']);
        }
    });
}