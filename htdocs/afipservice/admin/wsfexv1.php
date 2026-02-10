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
//include_once(DOL_DOCUMENT_ROOT.'/afipservice/class/wsaa_db.class.php');
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

    if (isset($_POST["AFIPWS_WSFEX_SERVER"]))   dolibarr_set_const($db, "AFIPWS_WSFEX_SERVER",   GETPOST("AFIPWS_WSFEX_SERVER"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFEX_PTOVTA"])) dolibarr_set_const($db, "AFIPWS_WSFEX_PTOVTA", GETPOST("AFIPWS_WSFEX_PTOVTA"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSFEX_MODE"]))    dolibarr_set_const($db, "AFIPWS_WSFEX_MODE",    GETPOST("AFIPWS_WSFEX_MODE"), 'yesno',0,'',$conf->entity);


    header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
    exit;
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
    'wsfexv1',
    $langs->trans("Module1050003Name"),
    -1,
    'afipservice@afipservice'
);



if ($action == 'edit') {

    $form=new Form($db);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // WSFEX server

    print '<tr '.$bc[$var].'><td>';
    $wsfexserver = (! empty($conf->global->AFIPWS_WSAA_SERVER)?$conf->global->AFIPWS_WSAA_SERVER:'https://wswhomo.afip.gov.ar/wsfexv1/service.asmx?WSDL');
    print $langs->trans("AFIPWS_WSFEX_SERVER");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFEX_SERVER" name="AFIPWS_WSFEX_SERVER" size="18" value="' . $wsfexserver . '">';
    print '</td></tr>';

    // WSFEX PTOVTA
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    $wsfexptovta = (! empty($conf->global->AFIPWS_WSFEX_PTOVTA) ? $conf->global->AFIPWS_WSFEX_PTOVTA : '2');
    print $langs->trans("AFIPWS_WSFEX_PTOVTA");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSFEX_PTOVTA" name="AFIPWS_WSFEX_PTOVTA" size="18" value="' . $wsfexptovta . '">';
    print '</td></tr>';


    // WSFEX MODE
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("AFIPWS_WSFEX_MODE");
    print '</td><td>';
    print $form->selectyesno('AFIPWS_WSFEX_MODE',$conf->global->AFIPWS_WSFEX_MODE,0);
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


    //WSFEX Server
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFEX_SERVER", ini_get('WSFESERVER') ? ini_get('WSFESERVER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFEX_SERVER) ? $conf->global->AFIPWS_WSFEX_SERVER : '') . '</td></tr>';
    // WSFEX PTOVTA
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFEX_PTOVTA", ini_get('WSFEPTOVTA') ? ini_get('WSFEPTOVTA') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSFEX_PTOVTA) ? $conf->global->AFIPWS_WSFEX_PTOVTA : '') . '</td></tr>';
   // WSFEX MODE
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSFEX_MODE") . '</td><td>' . yn($conf->global->AFIPWS_WSFEX_MODE) . '</td></tr>';

    print '</table>';

    // Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';

}

dol_fiche_end();
llxFooter();
