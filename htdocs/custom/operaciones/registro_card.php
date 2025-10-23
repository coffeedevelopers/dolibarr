<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       registro_card.php
 *		\ingroup    operaciones
 *		\brief      Page to create/edit/view registro
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/operaciones/class/registro.class.php');
dol_include_once('/operaciones/lib/operaciones_registro.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("operaciones@operaciones","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'registrocard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object=new Registro($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->operaciones->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('registrocard','globalcard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key]=GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
//$isdraft = (($object->statut == Registro::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'operaciones', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote=$user->rights->operaciones->write;	// Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->operaciones->write;	// Used by the include of actions_dellink.inc.php
$permissionedit=$user->rights->operaciones->write; // Used by the include of actions_lineupdown.inc.php
$permissiontoadd=$user->rights->operaciones->write; // Used by the include of actions_addupdatedelete.inc.php



/*
 * Actions
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    $error=0;

    $permissiontodelete = $user->rights->operaciones->delete || ($permissiontoadd && $object->status == 0);
    $backurlforlist = dol_buildpath('/operaciones/registro_list.php', 1);
    if (empty($backtopage)) {
       $backtopage = dol_buildpath('/operaciones/registro_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
    }
    $triggermodname = 'OPERACIONES_REGISTRO_MODIFY';	// Name of trigger action code to execute when we modify record

    // Actions cancel, add, update, delete or clone
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Actions when linking object each other
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Actions to send emails
    $trigger_name='REGISTRO_SENTBYMAIL';
    $autocopy='MAIN_MAIL_AUTOCOPY_REGISTRO_TO';
    $trackid='registro'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('', $langs->trans('Registro'), '');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';


// Part to create
if ($action == 'create')
{
    
    //if (!$object->timestartreal) $object->timestartreal = $object->timestart;
    //if (!$object->timeendreal) $object->timeendreal = $object->timeend;

    
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Registro")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	
	
	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');
	
	foreach($object->fields as $key => $val)
	{
	    // Discard if extrafield is a hidden field on form
	    if (abs($val['visible']) != 1) continue;
	    
	    if ($key=='datereal' || $key=='durationreal' || $key=='fk_desvio' || $key=='status' || $key=='ref') continue;
	    
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
	    
	    print '<tr id="field_'.$key.'">';
	    print '<td';
	    print ' class="titlefieldcreate';
	    if ($val['notnull'] > 0) print ' fieldrequired';
	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	    print '"';
	    print '>';
	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	    else print $langs->trans($val['label']);
	    print '</td>';
	    print '<td>';
	    if (in_array($val['type'], array('int', 'integer'))) $value = GETPOST($key, 'int');
	    elseif ($val['type'] == 'text' || $val['type'] == 'html') $value = GETPOST($key, 'none');
	    else $value = GETPOST($key, 'alpha');
	    print $object->showInputField($val, $key, $value, '', '', '', 0);
	    print '</td>';
	    print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("Registro"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	
	dol_fiche_head();

	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	
	$object->fields = dol_sort_array($object->fields, 'position');
	
	foreach($object->fields as $key => $val)
	{
	    // Discard if extrafield is a hidden field on form
	    if (abs($val['visible']) != 1 && abs($val['visible']) != 4) continue;
	    
	    //if ($key=='fk_desvio') continue;
	    
	    if ($key=='status' || $key=='ref'){
	        $val['noteditable']=1;
	    }
	    
	    if($object->status > 0){
	        if ($key=='fk_soc' || $key=='fk_user' || $key=='date' || $key=='duration' || $key=='fk_task'){
	            $val['noteditable']=1;
	        }
	    }
	    
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
	    
	    print '<tr><td';
	    print ' class="titlefieldcreate';
	    if ($val['notnull'] > 0) print ' fieldrequired';
	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	    print '">';
	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	    else print $langs->trans($val['label']);
	    print '</td>';
	    print '<td>';
	    if (in_array($val['type'], array('int', 'integer'))) $value = GETPOSTISSET($key)?GETPOST($key, 'int'):$object->$key;
	    elseif ($val['type'] == 'text' || $val['type'] == 'html') $value = GETPOSTISSET($key)?GETPOST($key, 'none'):$object->$key;
	    else $value = GETPOSTISSET($key)?GETPOST($key, 'alpha'):$object->$key;
	    //var_dump($val);
	    if ($value==0)$value="";
	    if ($val['noteditable']) print $object->showOutputField($val, $key, $value, '', '', '', 0);
	    else print $object->showInputField($val, $key, $value, '', '', '', 0);
	    print '</td>';
	    print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    $res = $object->fetch_optionals();

	$head = registroPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Registro"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteRegistro'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Clonar'), $langs->trans('Confirma la clonacion del Registro', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// pre validate
	if ($action == 'valid') {
	    $error = 0;
	    

	    
	    // Poner como olbigatorios los campos y validar el form
	    if (!$error){
	        $text = "Confirma la validacion? ";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Validar registro de Operaciones", $text, 'confirm_validate_registro', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm validate
	if ($action == 'confirm_validate_registro' && $confirm == "yes") {
	    $object->status = $object::STATUS_VALIDATED;
	    $object->ref = $object->getNextNumRef($object);
	    $object->update($user);
	    unset($action);
	}

	
	// pre complete
	if ($action == 'complete') {
	    $error = 0;
	    
	    // Poner como olbigatorios los campos y validar el form
	    if(!$object->datereal){
	        $object->errors[] = "Debe fijar la 'Fecha Real' para dar por completado el registro";
	        $error++;
	    }
	    if(!$object->durationreal){
	        $object->errors[] = "Debe fijar la 'Duracion Real' para dar por completado el registro";
	        $error++;
	    }
	    
	    // Poner como olbigatorios los campos y validar el form
	    if (!$error){
	        $text = "Confirma que desea marcar el registro como completado? No podra modificar el registro";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Completar registro de Operaciones", $text, 'confirm_complete_registro', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm complete
	if ($action == 'confirm_complete_registro' && $confirm == "yes") {
	    $object->status = $object::STATUS_APPROVED;
	    $object->update($user);
	    unset($action);
	}

	// pre nocomplete
	if ($action == 'nocomplete') {
	    $error = 0;
	    // Poner como olbigatorios los campos y validar el form
	    if(!$object->fk_desvio){
	        $object->errors[] = "Debe fijar el 'Desvio' para marcar como no cumplido";
	        $error++;
	    }
	    
	    // Poner como olbigatorios los campos y validar el form
	    if(!$object->datereal){
	        $object->errors[] = "Debe fijar la 'Fecha Real' para marcar como no cumplido";
	        $error++;
	    }
	    if(!$object->durationreal){
	        $object->errors[] = "Debe fijar la 'Duracion Real' para marcar como no cumplido";
	        $error++;
	    }
	    
	    // Poner como olbigatorios los campos y validar el form
	    if (!$error){
	        $text = "Confirma que desea marcar el registro como no completado? No podra modificar el registro";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Completar registro de Operaciones", $text, 'confirm_nocomplete_registro', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm nocomplete
	if ($action == 'confirm_nocomplete_registro' && $confirm == "yes") {
	    $object->status = $object::STATUS_REFUSED;
	    $object->update($user);
	    unset($action);
	}
	
	// pre refused
	if ($action == 'refused') {
	    $error = 0;
	    
	    // Poner como olbigatorios los campos y validar el form
	    if (!$error){
	        $text = "Confirma que desea marcar el registro cancelado?? No podra modificar el registro";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Completar registro de Operaciones", $text, 'confirm_refused_registro', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm nocomplete
	if ($action == 'confirm_refused_registro' && $confirm == "yes") {
	    $object->status = $object::STATUS_CANCELED;
	    $object->update($user);
	    unset($action);
	}
	
	// Call Hook formConfirm
	$parameters = array('lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' .dol_buildpath('/operaciones/registro_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->operaciones->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->operaciones->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->operaciones->write)
	    {
	        if ($action != 'classify')
	            $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	        }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.=$proj->getNomUrl();
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	*/
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';
	$object->fields = dol_sort_array($object->fields, 'position');
	
	foreach($object->fields as $key => $val)
	{
	    // Discard if extrafield is a hidden field on form
	    if (abs($val['visible']) != 1 && abs($val['visible']) != 4) continue;

	    if ($object->status== 0 && $key=='fk_desvio') continue;
	    
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
	    
	    $value=$object->$key;
	    if($value==0)$value='';
	    
	    print '<tr><td';
	    print ' class="titlefield fieldname_'.$key;
	    //if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	    print '">';
	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	    else print $langs->trans($val['label']);
	    print '</td>';
	    print '<td>';
	    print $object->showOutputField($val, $key, $value, '', '', '', 0);
	    //print dol_escape_htmltag($object->$key, 1, 1);
	    print '</td>';
	    print '</tr>';
	    
	    if (! empty($keyforbreak) && $key == $keyforbreak) break;						// key used for break on second column
	}
	
	print '</table>';
	
	// We close div and reopen for second column
	print '</div>';
	print '<div class="fichehalfright">';
	
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';
	
	$alreadyoutput = 1;
	foreach($object->fields as $key => $val)
	{
	    if ($alreadyoutput)
	    {
	        if (! empty($keyforbreak) && $key == $keyforbreak) $alreadyoutput = 0;		// key used for break on second column
	        continue;
	    }
	    
	    if (abs($val['visible']) != 1) continue;	// Discard such field from form
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! $val['enabled']) continue;	// We don't want this field
	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
	    
	    $value=$object->$key;
	    
	    print '<tr><td';
	    print ' class="titlefield fieldname_'.$key;
	    //if ($val['notnull'] > 0) print ' fieldrequired';		// No fieldrequired inthe view output
	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
	    print '">';
	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
	    else print $langs->trans($val['label']);
	    print '</td>';
	    print '<td>';
	    print $object->showOutputField($val, $key, $value, '', '', '', 0);
	    //print dol_escape_htmltag($object->$key, 1, 1);
	    print '</td>';
	    print '</tr>';
	}

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();


	/*
	 * Lines
	 */

	if (! empty($object->table_element_line))
	{
    	// Show object lines
    	$result = $object->getLinesArray();

    	print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid', 'int')) . '" method="POST">
    	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
    	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
    	<input type="hidden" name="mode" value="">
    	<input type="hidden" name="id" value="' . $object->id . '">
    	';

    	if (! empty($conf->use_javascript_ajax) && $object->status == 0) {
    	    include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
    	}

    	print '<div class="div-table-responsive-no-min">';
    	if (! empty($object->lines) && $object->status == 0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')
    	{
    	    print '<table id="tablelines" class="noborder noshadow" width="100%">';
    	}

    	if (! empty($object->lines))
    	{
    		$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);
    	}

    	// Form to add new line
    	if ($object->status == 0 && $permissiontoadd && $action != 'selectlines')
    	{
    	    if ($action != 'editline')
    	    {
    	        // Add products/services form
    	        $object->formAddObjectLine(1, $mysoc, $soc);

    	        $parameters = array();
    	        $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    	    }
    	}

    	if (! empty($object->lines) && $object->status == 0 && $permissiontoadd && $action != 'selectlines' && $action != 'editline')
    	{
    	    print '</table>';
    	}
    	print '</div>';

    	print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
    	print '<div class="tabsAction">'."\n";
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    	if (empty($reshook))
    	{
    	    if ($object->status == $object::STATUS_DRAFT)
    	    {
        	    // Validate
        	    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid"';
        	    print '>'.$langs->trans('Validate').'</a></div>';
                // Modify
                if (! empty($user->rights->operaciones->write))
        		{
        			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
        		}
        		else
        		{
        			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
        		}
    
        		// Clone
        		if (! empty($user->rights->operaciones->write))
        		{
        			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=order">' . $langs->trans("ToClone") . '</a></div>';
        		}
    
        		// Delete (need delete permission, or if draft, just need create/modify permission)
        		if (! empty($user->rights->operaciones->delete) || (! empty($object->fields['status']) && $object->status == $object::STATUS_DRAFT && ! empty($user->rights->operaciones->write)))
        		{
        			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Cancel').'</a>'."\n";
        		}
        		else
        		{
        			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Cancel').'</a>'."\n";
        		}
    	    }
    	    if ($object->status == $object::STATUS_VALIDATED)
    	    {
    	        // Completada
    	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=complete"';
    	        print '>'.$langs->trans('Cumplido').'</a></div>';
    	        // Validate
    	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=nocomplete"';
    	        print '>'.$langs->trans('No Cumplido').'</a></div>';
    	        // Modify
    	        if (! empty($user->rights->operaciones->write))
    	        {
    	            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
    	        }
    	        else
    	        {
    	            print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
    	        }
    	        
    	        // Clone
    	        if (! empty($user->rights->operaciones->write))
    	        {
    	            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=order">' . $langs->trans("ToClone") . '</a></div>';
    	        }
    	        
    	        // Delete (need delete permission, or if draft, just need create/modify permission)
    	        if (! empty($user->rights->operaciones->delete) || (! empty($object->fields['status']) && $object->status == $object::STATUS_DRAFT && ! empty($user->rights->operaciones->write)))
    	        {
    	            print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=refused">'.$langs->trans('Cancel').'</a>'."\n";
    	        }
    	        else
    	        {
    	            print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Cancel').'</a>'."\n";
    	        }
    	    }
    	}
    	print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend')
	{
	    print '<div class="fichecenter"><div class="fichehalfleft">';
	    print '<a name="builddoc"></a>'; // ancre

	    // Documents
	    /*$objref = dol_sanitizeFileName($object->ref);
	    $relativepath = $comref . '/' . $comref . '.pdf';
	    $filedir = $conf->operaciones->dir_output . '/' . $objref;
	    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	    $genallowed = $user->rights->operaciones->read;	// If you can read, you can build the PDF to read content
	    $delallowed = $user->rights->operaciones->create;	// If you can create/edit, you can remove a file on card
	    print $formfile->showdocuments('operaciones', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
		*/

	    // Show links to link elements
	    $linktoelem = $form->showLinkToObjectBlock($object, null, array('registro'));
	    $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.dol_buildpath('/operaciones/registro_agenda.php', 1).'?id='.$object->id.'">';
	    $morehtmlright.= $langs->trans("SeeAll");
	    $morehtmlright.= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    $somethingshown = $formactions->showactions($object, 'registro', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

	    print '</div></div></div>';
	}
}

// End of page
llxFooter();
$db->close();
