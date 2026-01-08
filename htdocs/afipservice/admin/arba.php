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


/*
 * Actions
 */



if ($action == 'update' && empty($_POST["cancel"]))
{
    // Send mode parameters

    //genero las carpetas para los XMLS
    $dirforxml=$conf->afipservice->dir_output.'/'.$conf->entity.'/xml/';
    if (! is_dir($dirforxml))
    {
        dol_mkdir($dirforxml);
    }
    // CRT
    $varforcrt='AFIPSERVICE_WSARBA_CRT';
    $dirforcrt=$conf->afipservice->dir_output.'/'.$conf->entity.'/keys/';
    if ($_FILES[$varforcrt]["tmp_name"])
    {
        if (preg_match('/([^\\/:]+)$/i',$_FILES[$varforcrt]["name"],$reg))
        {
            $original_file=$reg[1];

            if (! is_dir($dirforcrt))
            {
                dol_mkdir($dirforcrt);
            }
            $result=dol_move_uploaded_file($_FILES[$varforcrt]["tmp_name"],$dirforcrt.$original_file,1,0,$_FILES[$varforcrt]['error']);
            if ($result > 0) {
                dolibarr_set_const($db, "AFIPSERVICE_WSARBA_CRT", $original_file, 'chaine', 0, '', $conf->entity);
            }
            else
            {
                $error++;
                setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
            }

        }
    }
//--------

    if (isset($_POST["AFIPSERVICE_WSARBA_SERVER"]))   dolibarr_set_const($db, "AFIPSERVICE_WSARBA_SERVER",   GETPOST("AFIPSERVICE_WSARBA_SERVER"),'chaine',0,'',$conf->entity);
//    if (isset($_POST["AFIPSERVICE_WSARBA_CRT"])) dolibarr_set_const($db, "AFIPSERVICE_WSARBA_CRT", GETPOST("AFIPSERVICE_WSARBA_CRT"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPSERVICE_WSARBA_USER"]))    dolibarr_set_const($db, "AFIPSERVICE_WSARBA_USER",    GETPOST("AFIPSERVICE_WSARBA_USER"), 'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPSERVICE_WSARBA_PWD"]))    dolibarr_set_const($db, "AFIPSERVICE_WSARBA_PWD",    GETPOST("AFIPSERVICE_WSARBA_PWD"), 'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPSERVICE_WSARBA_MODE"]))    dolibarr_set_const($db, "AFIPSERVICE_WSARBA_MODE",    GETPOST("AFIPSERVICE_WSARBA_MODE"), 'yesno',0,'',$conf->entity);




    header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
    exit;
}

if ($action == 'removecrt')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $crtfile=$conf->afipservice->dir_output.'/'.$conf->entity.'/keys/'.$conf->global->AFIPSERVICE_WSARBA_CRT;
    dol_delete_file($crtfile);
    dolibarr_set_const($db, "AFIPSERVICE_WSARBA_CRT",      '', 'chaine',0,'',$conf->entity);
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
print load_fiche_titre($langs->trans("WSAAConfig"), $linkback, 'setup');

$head = afipserviceAdminPrepareHead();
dol_fiche_head(
    $head,
    'ARBA',
    $langs->trans("Module1050003Name"),
    -1,
    'afipservice@afipservice'
);



if ($action == 'edit') {

    $form=new Form($db);

    //print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';

    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // WSARBA server

    print '<tr '.$bc[$var].'><td>';
    $wsarbaserver = (! empty($conf->global->AFIPSERVICE_WSARBA_SERVER)?$conf->global->AFIPSERVICE_WSARBA_SERVER:'https://dfe.test.arba.gov.ar/DomicilioElectronico/SeguridadCliente/dfeServicioConsulta.do');
    print $langs->trans("AFIPSERVICE_WSARBA_SERVER");
    print '</td><td>';
    print '<input class="flat" id="AFIPSERVICE_WSARBA_SERVER" name="AFIPSERVICE_WSARBA_SERVER" size="18" value="' . $wsarbaserver . '">';
    print '</td></tr>';

    // WSARBA CRT
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsarbacrt = (! empty($conf->global->AFIPSERVICE_WSARBA_CRT) ? $conf->global->AFIPSERVICE_WSARBA_CRT : 'arba.crt');
    print $langs->trans("AFIPSERVICE_WSARBA_CRT");
    print '</td><td>';
    //print '<input class="flat" id="AFIPSERVICE_WSARBA_CRT" name="AFIPSERVICE_WSARBA_CRT" size="18" value="' . $wsarbacrt . '">';
    //print '</td></tr>';
    print '<table width="100%" class="nobordernopadding"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
    print '<input type="file" class="flat class=minwidth200" name="AFIPSERVICE_WSARBA_CRT" id="AFIPSERVICE_WSARBA_CRT">';
    print '</td><td class="nocellnopadd" valign="middle" align="right">';

    if (file_exists($conf->afipservice->dir_output.'/'.$conf->entity.'/keys/'.$wsarbacrt)) {
        print $wsarbacrt;
        print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removecrt">' . img_delete($langs->trans("Delete")) . '</a>';
    }
    print '</td></tr></table>';
    print '</td></tr>';


    // WSARBA USER
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsarbauser = (! empty($conf->global->AFIPSERVICE_WSARBA_USER) ? $conf->global->AFIPSERVICE_WSARBA_USER : '');
    print $langs->trans("AFIPSERVICE_WSARBA_USER");
    print '</td><td>';
    print '<input class="flat" id="AFIPSERVICE_WSARBA_USER" name="AFIPSERVICE_WSARBA_USER" size="18" value="' . $wsarbauser . '">';
    print '</td></tr>';



    // WSARBA PASSWORD
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsarbapwd = (! empty($conf->global->AFIPSERVICE_WSARBA_PWD) ? $conf->global->AFIPSERVICE_WSARBA_PWD : '');
    print $langs->trans("AFIPSERVICE_WSARBA_PWD");
    print '</td><td>';
    print '<input class="flat" id="AFIPSERVICE_WSARBA_PWD" name="AFIPSERVICE_WSARBA_PWD" size="18" value="' . $wsarbapwd . '">';
    print '</td></tr>';


    // WSARBA MODE
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("AFIPSERVICE_WSARBA_MODE");
    print '</td><td>';
    print $form->selectyesno('AFIPSERVICE_WSARBA_MODE',$conf->global->AFIPSERVICE_WSARBA_MODE,1);
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


    //WSARBA Server
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPSERVICE_WSARBA_SERVER", ini_get('WSARBASERVER') ? ini_get('WSARBASERVER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPSERVICE_WSARBA_SERVER) ? $conf->global->AFIPSERVICE_WSARBA_SERVER : '') . '</td></tr>';
    // WSARBA CRT
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPSERVICE_WSARBA_CRT", ini_get('WSARBACRT') ? ini_get('WSARBACRT') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPSERVICE_WSARBA_CRT) ? $conf->global->AFIPSERVICE_WSARBA_CRT : '') . '</td></tr>';
    // WSARBA USER
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPSERVICE_WSARBA_USER", ini_get('WSARBAUSER') ? ini_get('WSARBAUSER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPSERVICE_WSARBA_USER) ? $conf->global->AFIPSERVICE_WSARBA_USER : '') . '</td></tr>';
    // WSARBA PWD
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPSERVICE_WSARBA_PWD", ini_get('WSARBAPWD') ? ini_get('WSARBAPWD') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPSERVICE_WSARBA_PWD) ? $conf->global->AFIPSERVICE_WSARBA_PWD : '') . '</td></tr>';

    // WSARBA MODE
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPSERVICE_WSARBA_MODE") . '</td><td>' . yn($conf->global->AFIPSERVICE_WSARBA_MODE) . '</td></tr>';

    print '</table>';

    // Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';

}

dol_fiche_end();
llxFooter();
