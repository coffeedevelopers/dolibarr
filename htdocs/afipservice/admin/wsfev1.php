<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file admin/wsaa.php
 * \ingroup finacial
 * Module configuration page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/afipservice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

global $conf, $db, $user, $langs;

$mesg = ""; // User message

$langs->load('afipservice@afipservice');
$langs->load('admin');
$langs->load('help');

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
// Get parameters
$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');

$wsaaprod= GETPOST('wsaaprod');
$cetificate=GETPOST('crt');
$privatekey=GETPOST('key');
$modo=GETPOST('modo');
$emisorcuit = str_replace("-","",$conf->global->MAIN_INFO_SIREN);


/*
 * Actions
 */



if ($action == 'update' && empty($_POST["cancel"]))
{
    // Send mode parameters

    // PDF
    $varforkey='AFIPWS_WSFE_PDF_TEMPLATE';
    $dirforkey=$conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/';
    if ($_FILES[$varforkey]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforkey]["name"],$reg))
        {
            $original_file=$reg[1];

            if (! is_dir($dirforkey))
            {
                dol_mkdir($dirforkey);
            }
            $result=dol_move_uploaded_file($_FILES[$varforkey]["tmp_name"],$dirforkey.$original_file,1,0,$_FILES[$varforkey]['error']);
            if ($result > 0) {
                dolibarr_set_const($db, "AFIPWS_WSFE_PDF_TEMPLATE", $original_file, 'chaine', 0, '', $conf->entity);
            }
            else
            {
                $error++;
                setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            }

        }
    }
//--------

    // PDF
    $varforkey='AFIPWS_WSFE_PDF_CSV';
    $dirforkey=$conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/';
    if ($_FILES[$varforkey]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforkey]["name"],$reg))
        {
            $original_file=$reg[1];

            if (! is_dir($dirforkey))
            {
                dol_mkdir($dirforkey);
            }
            $result=dol_move_uploaded_file($_FILES[$varforkey]["tmp_name"],$dirforkey.$original_file,1,0,$_FILES[$varforkey]['error']);
            if ($result > 0) {
                dolibarr_set_const($db, "AFIPWS_WSFE_PDF_CSV", $original_file, 'chaine', 0, '', $conf->entity);
            }
            else
            {
                $error++;
                setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            }

        }
    }
//--------

    if (isset($_POST["AFIPWS_WSFE_SERVER"]))   dolibarr_set_const($db, "AFIPWS_WSFE_SERVER",   GETPOST("AFIPWS_WSFE_SERVER"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFE_PTOVTA"])) dolibarr_set_const($db, "AFIPWS_WSFE_PTOVTA", GETPOST("AFIPWS_WSFE_PTOVTA"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFE_MODE"]))    dolibarr_set_const($db, "AFIPWS_WSFE_MODE",    GETPOST("AFIPWS_WSFE_MODE"), 'yesno',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFE_PDF_COPIES"]))    dolibarr_set_const($db, "AFIPWS_WSFE_PDF_COPIES",    GETPOST("AFIPWS_WSFE_PDF_COPIES"), 'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFE_PDF_LABELS"]))    dolibarr_set_const($db, "AFIPWS_WSFE_PDF_LABELS",    GETPOST("AFIPWS_WSFE_PDF_LABELS"), 'yesno',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFE_PDF_INICIOACT"]))    dolibarr_set_const($db, "AFIPWS_WSFE_PDF_INICIOACT",    GETPOST("AFIPWS_WSFE_PDF_INICIOACT"), 'chaine',0,'',$conf->entity);


    header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
    exit;
}

if ($action == 'removepdf')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $pdffile=$conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/'.$conf->global->AFIPWS_WSFE_PDF_TEMPLATE;
    dol_delete_file($pdffile);
    dolibarr_set_const($db, "AFIPWS_WSFE_PDF_TEMPLATE",      '', 'chaine',0,'',$conf->entity);

}

if ($action == 'removecsv')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $csvfile=$conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/'.$conf->global->AFIPWS_WSFE_PDF_CSV;
    dol_delete_file($csvfile);
    dolibarr_set_const($db, "AFIPWS_WSFE_PDF_CSV",      '', 'chaine',0,'',$conf->entity);
}


/**
 * view
 */
llxHeader();
// Error / confirmation messages
dol_htmloutput_mesg($mesg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');

$head = afipserviceAdminPrepareHead();
dol_fiche_head(
    $head,
    'wsfev1',
    $langs->trans("Module1050003Name"),
    -1,
    'afipservice@afipservice'
);



if ($action == 'edit') {

    $form=new Form($db);

//    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';

    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // WSFE server
    print '<tr '.$bc[$var].'><td>';
    $wsfeserver = (! empty($conf->global->AFIPWS_WSFE_SERVER)?$conf->global->AFIPWS_WSFE_SERVER:'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL');
    print $langs->trans("AFIPWS_WSFE_SERVER");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFE_SERVER" name="AFIPWS_WSFE_SERVER" size="18" value="' . $wsfeserver . '">';
    print '</td></tr>';

    // WSFE PTOVTA
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfeptovta = (! empty($conf->global->AFIPWS_WSFE_PTOVTA) ? $conf->global->AFIPWS_WSFE_PTOVTA : '2');
    print $langs->trans("AFIPWS_WSFE_PTOVTA");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFE_PTOVTA" name="AFIPWS_WSFE_PTOVTA" size="18" value="' . $wsfeptovta . '">';
    print '</td></tr>';


    // WSFE MODE
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $text = $langs->trans("AFIPWS_WSFE_MODE");
    $htmltext = $langs->trans("WSFEYesNoModeMessaje");
    print $form->textwithpicto($text,$htmltext,1,'info');

    print '</td><td>';
    print $form->selectyesno('AFIPWS_WSFE_MODE',$conf->global->AFIPWS_WSFE_MODE,1);
    print '</td></tr>';

    // WSFE PDF COPIES
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfecopies = (! empty($conf->global->AFIPWS_WSFE_PDF_COPIES) ? $conf->global->AFIPWS_WSFE_PDF_COPIES : '1');
    print $langs->trans("AFIPWS_WSFE_PDF_COPIES");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFE_PDF_COPIES" name="AFIPWS_WSFE_PDF_COPIES" size="18" value="' . $wsfecopies . '">';
    print '</td></tr>';

    // WSFE PDF LABELS
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $text = $langs->trans("AFIPWS_WSFE_PDF_LABELS");
    $htmltext = $langs->trans("WSFEYesNoPDFLabels");
    print $form->textwithpicto($text,$htmltext,1,'info');

    print '</td><td>';
    print $form->selectyesno('AFIPWS_WSFE_PDF_LABELS',$conf->global->AFIPWS_WSFE_PDF_LABELS,1);
    print '</td></tr>';


    // WSFE INICIO ACTIVIDADES
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfeinicioact = (! empty($conf->global->AFIPWS_WSFE_PDF_INICIOACT) ? $conf->global->AFIPWS_WSFE_PDF_INICIOACT : '01/01/2000');
    print $langs->trans("AFIPWS_WSFE_PDF_INICIOACT");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFE_PDF_INICIOACT" name="AFIPWS_WSFE_PDF_INICIOACT" size="18" value="' . $wsfeinicioact . '">';
    print '</td></tr>';


    // PDF
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfepdf = (! empty($conf->global->AFIPWS_WSFE_PDF_TEMPLATE) ? $conf->global->AFIPWS_WSFE_PDF_TEMPLATE : '');
    print $langs->trans("AFIPWS_WSFE_PDF_TEMPLATE");
    print '</td><td>';
    print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
    print '<input type="file" class="flat class=minwidth200" name="AFIPWS_WSFE_PDF_TEMPLATE" id="AFIPWS_WSFE_PDF_TEMPLATE">';
    print '</td><td class="nocellnopadd" valign="middle" align="right">';

    if (file_exists($conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/'.$wsfepdf)) {
        print $wsfepdf;
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removepdf">' . img_delete($langs->trans("Delete")) . '</a>';
    }
    print '</td></tr></table>';
    print '</td></tr>';

    // CSV
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfecsv = (! empty($conf->global->AFIPWS_WSFE_PDF_CSV) ? $conf->global->AFIPWS_WSFE_PDF_CSV : '');
    print $langs->trans("AFIPWS_WSFE_PDF_CSV");
    print '</td><td>';
    print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
    print '<input type="file" class="flat class=minwidth200" name="AFIPWS_WSFE_PDF_CSV" id="AFIPWS_WSFE_PDF_CSV">';
    print '</td><td class="nocellnopadd" valign="middle" align="right">';

    if (file_exists($conf->afipservice->dir_output.'/'.$conf->entity.'/wsfev1/'.$wsfecsv)) {
        print $wsfecsv;
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removecsv">' . img_delete($langs->trans("Delete")) . '</a>';
    }
    print '</td></tr></table>';
    print '</td></tr>';


    print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</center>';

    print '</form>';
    print '<br>';

}else {

    $var = true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';


    //WSFE Server
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_SERVER", ini_get('WSFESERVER') ? ini_get('WSFESERVER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_SERVER) ? $conf->global->AFIPWS_WSFE_SERVER : '') . '</td></tr>';
    // WSFE PTOVTA
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PTOVTA", ini_get('WSFEPTOVTA') ? ini_get('WSFEPTOVTA') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_PTOVTA) ? $conf->global->AFIPWS_WSFE_PTOVTA : '') . '</td></tr>';
   // WSFE MODE
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_MODE") . '</td><td>' . yn($conf->global->AFIPWS_WSFE_MODE) . '</td></tr>';
    // WSFE PDF COPIES
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PDF_COPIES", ini_get('WSFECOPIES') ? ini_get('WSFECOPIES') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_PDF_COPIES) ? $conf->global->AFIPWS_WSFE_PDF_COPIES : '') . '</td></tr>';

    // WSFE PDF LABELS
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PDF_LABELS") . '</td><td>' . yn($conf->global->AFIPWS_WSFE_PDF_LABELS) . '</td></tr>';

    // WSFE INICO ACTIVIDADES
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PDF_INICIOACT", ini_get('WSFEINICIOACT') ? ini_get('WSFEINICIOACT') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_PDF_INICIOACT) ? $conf->global->AFIPWS_WSFE_PDF_INICIOACT : '') . '</td></tr>';


    // PDF
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PDF_TEMPLATE", ini_get('WSFEPDF') ? ini_get('WSFEPDF') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_PDF_TEMPLATE) ? $conf->global->AFIPWS_WSFE_PDF_TEMPLATE : '') . '</td></tr>';

    // CSV
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFE_PDF_CSV", ini_get('WSFECSV') ? ini_get('WSFECSV') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFE_PDF_CSV) ? $conf->global->AFIPWS_WSFE_PDF_CSV : '') . '</td></tr>';

    print '</table>';

    // Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';

}

dol_fiche_end();
llxFooter();
