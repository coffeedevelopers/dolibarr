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
 * \file admin/support.php
 * \ingroup financial
 * Module support page
 */


$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory


require_once DOL_DOCUMENT_ROOT . '/afipws/lib/afipws.lib.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsfev1.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipws/exceptionhandler.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';




global $langs, $user;


$langs->load('afipws@afipws');
$langs->load('admin');
$langs->load('help');

// only readable by admin
if (!$user->admin) {
    accessforbidden();
}


// Parameters
$action = GETPOST('action', 'alpha');
// Get parameters
$cbttipo = GETPOST('cbttipo');
$ptovta	= GETPOST('ptovta');
$cbtnro=GETPOST('cbtnro');

//$wsaadb=new wsaa_db($db);
$cuitemisor = str_replace("-","",$conf->global->MAIN_INFO_SIREN);
//$wsaadb->entity=$_SESSION['dol_entity'];
//$wsaadb->fetch($emisorcuit);

/*****************
//WSFEV1
 ****************/
$wsfev1 = new wsfev1();
if ($wsfev1->error) {
    $error .= $wsfev1->error;
}
/*****************
 * //WSAA
 ****************/
$wsaa = new wsaa();
if ($wsaa->error) {
    $error .= $wsaa->error;
}



if ($error) {
    $action='';
    $dummy=$error;
}else{
    $dummy = json_encode($wsfev1->FEDummy());

}

/*
 * Actions
 */

if ($action =='request'){
    $wsfev1->openTA();
    $comprobate=$wsfev1->FECompConsultar($cbttipo,$cbtnro,$ptovta,$cuitemisor);
}

if ($action == 'renewta') {
//    $wsaa->service='wsfe'; //renover TA para WSFE //en desarrollo
    $wsaa->generar_TA();

}

if ($action == 'delete'){
    $urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
    $path=$conf->afipws->dir_output.'/';
    $ret = dol_delete_file($path.$urlfile, 0, 0, 0, (is_object($object)?$object:null));

}

/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
dol_htmloutput_mesg($mesg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');
$head = afipwsAdminPrepareHead();
dol_fiche_head($head, 'serverstatus', $langs->trans("Module1050003Name"), -1, 'afipws@afipws');


print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="renewta" />';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("ServerStatus").'</td>';
print '</tr>'."\n";
print '<tr>';
print '<td><textarea readonly rows="4" cols="100">'.$dummy.'</textarea></td>';
print '</tr>';
print '<br>';

print '</table>';
print '<input class="button"  value="'.$langs->trans("Renewta").'" type="submit">';
print '</form>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="request" />';
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("InvoiceInfo").'</td>';
print '</tr>'."\n";
print '<td width="17">'.$langs->trans("TypeInvoice").'</td>';
print '<td width="26"><input name="cbttipo" type="text" id="cbttipo" value="" maxlength="50" /></td>';
print '</tr>';
print '<tr>';
print '<td width="174">'.$langs->trans("POS").'</td>';
print '<td width="267"><input name="ptovta" type="text" id="ptovta" value="" maxlength="200" /></td>';
print '</tr>';
print '<tr>';
print '<td width="174">'.$langs->trans("InvoiceNumber").'</td>';
print '<td width="267"><input name="cbtnro" type="text" id="cbtnro" value="" maxlength="200" /></td>';
print '</tr>';

if ($comprobate) {
print '<tr>';
print '<td width="174">'.$langs->trans("Result").'</td>';
print '<td><textarea readonly rows="4" cols="100">'.json_encode($comprobate).'</textarea></td>';
}
print '</tr>';

print '</table>';

print '<input class="button"  value="'.$langs->trans("Request").'" type="submit">';

print '<br>';
print '</form>';

$upload_dir = $conf->afipws->dir_output.'/'.$conf->entity.'/xml/';
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

$formfile=new FormFile($db);

print '<br>';
$formfile->list_of_documents($filearray,'','afipws','',0,$conf->entity.'/xml/',1,0,'',0,'','',0);







dol_fiche_end();
llxFooter();
