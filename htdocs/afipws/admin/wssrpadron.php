<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 * Copyright (C) 2017 Primetec <info@primetec.com.ar> https://primetec.com.ar
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
 * \file admin/wssrpadron.php
 * \ingroup finacial
 * Module configuration page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/afipws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
//include_once(DOL_DOCUMENT_ROOT.'/afipws/class/wsaa_db.class.php');
global $conf, $db, $user, $langs;

$mesg = ""; // User message

$langs->load('afipws@afipws');
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

$wssrpadronprod= GETPOST('wsaaprod');
$modo=GETPOST('modo');


/*
 * Actions
 */



if ($action == 'update' && empty($_POST["cancel"]))
{
    // Send mode parameters

    if (isset($_POST["AFIPWS_WSSRPADRON_SERVER"]))   dolibarr_set_const($db, "AFIPWS_WSSRPADRON_SERVER",   GETPOST("AFIPWS_WSSRPADRON_SERVER"),'chaine',0,'',$conf->entity);
    if (isset($_POST["AFIPWS_WSSRPADRON_MODE"]))    dolibarr_set_const($db, "AFIPWS_WSSRPADRON_MODE",    GETPOST("AFIPWS_WSSRPADRON_MODE"), 'yesno',0,'',$conf->entity);


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
print load_fiche_titre($langs->trans("WSSRPADRONConfig"), $linkback, 'setup');

$head = afipwsAdminPrepareHead();
dol_fiche_head(
    $head,
    'wssrpadron',
    $langs->trans("Module1050003Name"),
    -1,
    'afipws@afipws'
);



if ($action == 'edit') {

    $form=new Form($db);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // WSSRPADRON server
    print '<tr '.$bc[$var].'><td>';
    $wssrpadron = (! empty($conf->global->AFIPWS_WSSRPADRON_SERVER)?$conf->global->AFIPWS_WSSRPADRON_SERVER:'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA4?WSDL');
    print $langs->trans("AFIPWS_WSSRPADRON_SERVER");
    print '</td><td>';
    print '<input class="flat" id="AFIPWS_WSSRPADRON_SERVER" name="AFIPWS_WSSRPADRON_SERVER" size="18" value="' . $wssrpadron . '">';
    print '</td></tr>';

 
    // WSSRPADRON MODE
    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    //print $langs->trans("AFIPWS_WSSRPADRON_MODE");
    $text = $langs->trans("AFIPWS_WSSRPADRON_MODE");
    $htmltext = $langs->trans("WSSRPADRONYesNoModeMessaje");
    print $form->textwithpicto($text,$htmltext,1,'info');

    print '</td><td>';
    print $form->selectyesno('AFIPWS_WSSRPADRON_MODE',$conf->global->AFIPWS_WSSRPADRON_MODE,1);
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


    //WSSRPADRON Server
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSSRPADRON_SERVER", ini_get('WSSRPADRONSERVER') ? ini_get('WSSRPADRONSERVER') : $langs->transnoentities("Undefined")) . '</td><td>' . (!empty($conf->global->AFIPWS_WSSRPADRON_SERVER) ? $conf->global->AFIPWS_WSSRPADRON_SERVER : '') . '</td></tr>';
   // WSSRPADRON MODE
    $var = !$var;
    print '<tr ' . $bc[$var] . '><td>' . $langs->trans("AFIPWS_WSSRPADRON_MODE") . '</td><td>' . yn($conf->global->AFIPWS_WSSRPADRON_MODE) . '</td></tr>';
    // WSSRPADRON PDF COPIES


    print '</table>';

    // Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit">' . $langs->trans("Modify") . '</a>';
    print '</div>';

}

dol_fiche_end();
llxFooter();
