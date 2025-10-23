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
 *   	\file       control_card.php
 *		\ingroup    asignaciones
 *		\brief      Page to create/edit/view control
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
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
dol_include_once('/asignaciones/class/control.class.php');
dol_include_once('/asignaciones/class/asignacion.class.php');
dol_include_once('/holiday/class/holiday.class.php');
dol_include_once('/asignaciones/lib/asignaciones_control.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("asignaciones@asignaciones","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'controlcard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object=new Control($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->asignaciones->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('controlcard','globalcard'));     // Note that conf->hooks_modules contains array
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
//$isdraft = (($object->statut == Control::STATUS_DRAFT) ? 1 : 0);
//$result = restrictedArea($user, 'asignaciones', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote=$user->rights->asignaciones->write;	// Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->asignaciones->write;	// Used by the include of actions_dellink.inc.php
$permissionedit=$user->rights->asignaciones->write; // Used by the include of actions_lineupdown.inc.php
$permissiontoadd=$user->rights->asignaciones->write; // Used by the include of actions_addupdatedelete.inc.php



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

    $permissiontodelete = $user->rights->asignaciones->delete || ($permissiontoadd && $object->status == 0);
    $backurlforlist = dol_buildpath('/asignaciones/control_list.php', 1);
    if (empty($backtopage)) {
        if (empty($id)) $backtopage = $backurlforlist;
        else $backtopage = dol_buildpath('/asignaciones/control_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
    }
    $triggermodname = 'ASIGNACIONES_CONTROL_MODIFY';	// Name of trigger action code to execute when we modify record

    // Actions cancel, add, update, delete or clone
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Actions when linking object each other
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Actions to send emails
    $trigger_name='CONTROL_SENTBYMAIL';
    $autocopy='MAIN_MAIL_AUTOCOPY_CONTROL_TO';
    $trackid='control'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 *
 * Put here all code to build page
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('', $langs->trans('Control'), '');

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
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Control")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="status" value="1">';
	
	
	$object->fields['fk_asignacion']['visible']=0;
	$object->fields['fk_control_reemplazo']['visible']=0;
	
	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

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
	print load_fiche_titre($langs->trans("Control"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	
	if (!$object->timestartreal) $object->timestartreal = $object->timestart;
	if (!$object->timeendreal) $object->timeendreal = $object->timeend;
	
	$object->fields['timestart']['noteditable']=1;
	$object->fields['timeend']['noteditable']=1;
	$object->fields['fk_user']['noteditable']=1;
	$object->fields['ref']['noteditable']=1;
	$object->fields['fk_soc']['noteditable']=1;
	$object->fields['fk_asignacion']['visible']=0;
	$object->fields['fk_control_reemplazo']['visible']=0;

	dol_fiche_head();

	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

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

	$head = controlPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Control"), -1, $object->picto);
	
	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteControl'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
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
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneControl', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion=array();
	    /*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
	    $formquestion = array(
	        // 'text' => $langs->trans("ConfirmClone"),
	        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
	        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
	        // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
        );
	    */
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}
	
	// pre validate
	if ($action == 'valid') {
	    $error = 0;
	    
	    // Poner como olbigatorios los campos y validar el form
	    if(!$object->timestartreal){
	        $object->errors[] = "Debe fijar la fecha de 'Inicio Real' para validar el control";
	        $error++;
	    }
	    if(!$object->timeendreal){
	        $object->errors[] = "Debe fijar la fecha de 'Finalizacion Real' para validar el control";
	        $error++;
	    }
	    if (!$error){
	       $text = "Confirma la validacion? No podra modificar el registro";
           $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Validar asignaci�n diaria", $text, 'confirm_validate_control', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// pre validate
	if ($action == 'replace') {
	    $error = 0;
	    
	    $rrhh = new Holiday($db);
	    $types = $rrhh->getTypes();
	    
	    $formquestion['text'] ='Se generara una novedad en el modulo de RRHH y un Ticket en el módulo de Tickets<br>';
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'holiday_types',
	        'label' => $langs->trans("Tipo de novedad: "),
	        'value' => $form->selectarray('holiday_types', $types));

	    $types = array();
	    // Ticket Type
	    $sql = "SELECT rowid, code, label, use_default, pos, description";
	    $sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_type";
	    $sql .= " WHERE active > 0";
	    $sql .= " ORDER BY pos";
	    $resql = $db->query($sql);
	    if ($resql) {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        while ($i < $num) {
	            $obj = $db->fetch_object($resql);
	            $types[$obj->code] = $obj->label;
	            $i++;
	        }
	    }
	    
	    $formquestion[]=array('type' => 'checkbox',
	        'name' => 'generaticket',
	        'label' => "Generar Ticket ? ");
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_types',
	        'label' => "Tipo de Ticket: ",
	        'value' => $form->selectarray('ticket_types', $types));
	    
	    
	    // Severity
	    $types = array();
	    $sql = "SELECT rowid, code, label, use_default, pos, description";
	    $sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity";
	    $sql .= " WHERE active > 0";
	    $sql .= " ORDER BY pos";
	    $resql = $db->query($sql);
	    if ($resql) {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        while ($i < $num) {
	            $obj = $db->fetch_object($resql);
	            $types[$obj->code] = $obj->label;
	            $i++;
	        }
	    }
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_severity',
	        'label' => "Severidad del Ticket: ",
	        'value' => $form->selectarray('ticket_severity', $types));
	    
	    $extrafields2 = new ExtraFields($db);
	    $extrafields2->fetch_name_optionals_label('ticket');
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_clasificacion',
	        'label' => $langs->trans("Clasificacion del Ticket: "),
	        'value' => $form->selectarray('ticket_clasificacion', $extrafields2->attributes['ticket']['param']['clasificacion']['options']));
	    
	    
	    if (!$error){
	       $text = "Confirma el reemplazo?";
	       $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "", $text, 'confirm_replace', $formquestion, 0, 1,320,800);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	if ($action == 'rrhh_news') {
	    $error = 0;
	    
	    $rrhh = new Holiday($db);
	    $types = $rrhh->getTypes();
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'holiday_types',
	        'label' => $langs->trans("Tipo de novedad"),
	        'value' => $form->selectarray('holiday_types', $types));
	    
	    if (!$error){
	        $text = "Generar una novedad en el modulo de RRHH?";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "", $text, 'confirm_rrhh_news', $formquestion, 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	if ($action == 'ticket') {
	    $error = 0;

	    $types = array();
	    // Ticket Type
	    $sql = "SELECT rowid, code, label, use_default, pos, description";
	    $sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_type";
	    $sql .= " WHERE active > 0";
	    $sql .= " ORDER BY pos";
	    $resql = $db->query($sql);
	    if ($resql) {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        while ($i < $num) {
	            $obj = $db->fetch_object($resql);
	            $types[$obj->code] = $obj->label;
	            $i++;
	        }
	    }
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_types',
	        'label' => $langs->trans("Tipo de Ticket"),
	        'value' => $form->selectarray('ticket_types', $types));
	    
	    
	    // Severity
	    $types = array();
	    $sql = "SELECT rowid, code, label, use_default, pos, description";
	    $sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity";
	    $sql .= " WHERE active > 0";
	    $sql .= " ORDER BY pos";
	    $resql = $db->query($sql);
	    if ($resql) {
	        $num = $db->num_rows($resql);
	        $i = 0;
	        while ($i < $num) {
	            $obj = $db->fetch_object($resql);
	            $types[$obj->code] = $obj->label;
	            $i++;
	        }
	    }
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_severity',
	        'label' => $langs->trans("Severidad"),
	        'value' => $form->selectarray('ticket_severity', $types));
	    
	    $extrafields2 = new ExtraFields($db);
	    $extrafields2->fetch_name_optionals_label('ticket');
	    
	    $formquestion[]=array('type' => 'other',
	        'name' => 'ticket_clasificacion',
	        'label' => $langs->trans("Clasificacion"),
	        'value' => $form->selectarray('ticket_clasificacion', $extrafields2->attributes['ticket']['param']['clasificacion']['options']));
	    
	    if (!$error){
	        $text = "Generar un ticket en el modulo de Tickets?";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "", $text, 'confirm_ticket', $formquestion, 0, 1,320,800);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm validate
	if ($action == 'confirm_validate_control' && $confirm == "yes") {
	    $object->status = $object::STATUS_APPROVED;
	    $object->update($user);
	    unset($action);
	    setEventMessages("El control fue validado", '', 'warnings');
	}
	
	// Confirm replace
	if ($action == 'confirm_replace' && $confirm == "yes") {
	    // Si valido generar la novedad
	    $novedad = new Holiday($db);
	    $novedad->fk_user = $object->fk_user;
	    $novedad->fk_type = GETPOST('holiday_types','int') ? GETPOST('holiday_types','int'): 1; // Tipo de Novedad
	    $novedad->fk_validator = 18; // Validador
	    $novedad->description = "Novedad generada automaticamente por el modulo Asignaciones";
	    $novedad->date_debut = $object->timestart;//date("Y-m-d",$object->timestart);
	    $novedad->date_fin = $object->timeend;//date("Y-m-d",$object->timeend);
	    $novedad->halfday = 0;
	    $novedad->create($user);
	    
	    setEventMessages("Se generó la novedad ".$novedad->getNomUrl(1), '', 'warnings');
	    
	    if(GETPOST('generaticket','alpha') == 'on'){
	        // Si valido generar el ticket
	        $ticket = new Ticket($db);
	        $ticket->ref = $ticket->getDefaultRef();
	        $ticket->fk_soc = $object->fk_soc ? $object->fk_soc : '';
	        // Tipo
	        $ticket->type_code = GETPOST('ticket_types','alpha') ? GETPOST('ticket_types','alpha'): 1; // Tipo de Novedad
	        $ticket->severity_code = GETPOST('ticket_severity','alpha') ? GETPOST('ticket_severity','alpha'): 1; // Tipo de Novedad
	        // Mensaje: Relacionar con asignacion
	        $usuario = new User($db);
	        $usuario->fetch($object->fk_user);
	        $ticket->message = "Ticket generado automaticamente por el modulo Asignaciones<br>Usuario ".$usuario->getNomUrl(1)."<br>Control ".$object->getNomUrl(1);
	        
	        $ticket->subject = "Ticket generado automaticamente por el modulo Asignaciones";
	        $ticket->statut = 0;
	        $ticket->create($user,1);
	        
	        $region = $object->fk_region;
	        $clasificacion = GETPOST('ticket_clasificacion','alpha') ? GETPOST('ticket_clasificacion','alpha'): 1;
	        
	        $sql = "INSERT INTO `llx_ticket_extrafields` (`rowid`, `tms`, `fk_object`, `import_key`, `migrado`, `sucursal`, `clasificacion`, `clasificacioncierre`) ";
	        $sql .= "VALUES (NULL, CURRENT_TIMESTAMP, '$ticket->id', NULL, NULL, $region , $clasificacion, NULL);";
	        
	        $db->query($sql);
	        
	        setEventMessages("Se generó el ticket ".$ticket->getNomUrl(1), '', 'warnings');
	    }
	    
	    //Cambio estado
	    $object->status = $object::STATUS_REFUSED;
	    $object->update($user);

	}

	// Confirm rrhh news
	if ($action == 'confirm_rrhh_news' && $confirm == "yes") {
	    // Si valido generar la novedad
	    $novedad = new Holiday($db);
	    $novedad->fk_user = $object->fk_user;
	    $novedad->fk_type = GETPOST('holiday_types','int') ? GETPOST('holiday_types','int'): 1; // Tipo de Novedad
	    $novedad->fk_validator = 18; // Validador
	    $novedad->description = "Novedad generada automaticamente por el modulo Asignaciones";
	    $novedad->date_debut = $object->timestart;//date("Y-m-d",$object->timestart);
	    $novedad->date_fin = $object->timeend;//date("Y-m-d",$object->timeend);
	    $novedad->halfday = 0;
	    $novedad->create($user);
	    
	    setEventMessages("Se generó la novedad ".$novedad->getNomUrl(1), '', 'warnings');
	}
	
	// Confirm ticket
	if ($action == 'confirm_ticket' && $confirm == "yes") {
	    // Si valido generar el ticket
	    $ticket = new Ticket($db);
	    $ticket->ref = $ticket->getDefaultRef();
	    $ticket->fk_soc = $object->fk_soc ? $object->fk_soc : '';
	    // Tipo
	    $ticket->type_code = GETPOST('ticket_types','alpha') ? GETPOST('ticket_types','alpha'): 1; // Tipo de Novedad
	    $ticket->severity_code = GETPOST('ticket_severity','alpha') ? GETPOST('ticket_severity','alpha'): 1; // Tipo de Novedad
	    // Mensaje: Relacionar con asignacion
	    $usuario = new User($db);
	    $usuario->fetch($object->fk_user);
	    $ticket->message = "Ticket generado automaticamente por el modulo Asignaciones<br>Usuario ".$usuario->getNomUrl(1)."<br>Control ".$object->getNomUrl(1);
	    
	    $ticket->subject = "Ticket generado automaticamente por el modulo Asignaciones";
	    $ticket->statut = 0;
	    $ticket->create($user,1);
	    
	    $region = $object->fk_region;
	    $clasificacion = GETPOST('ticket_clasificacion','alpha') ? GETPOST('ticket_clasificacion','alpha'): 1;
	    
	    $sql = "INSERT INTO `llx_ticket_extrafields` (`rowid`, `tms`, `fk_object`, `import_key`, `migrado`, `sucursal`, `clasificacion`, `clasificacioncierre`) ";
	    $sql .= "VALUES (NULL, CURRENT_TIMESTAMP, '$ticket->id', NULL, NULL, $region , $clasificacion, NULL);";
	    
	    $db->query($sql);
	    	    
	    $ticket->create($user,1);
	    
	    setEventMessages("Se generó el ticket ".$ticket->getNomUrl(1), '', 'warnings');
	    
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
	$linkback = '<a href="' .dol_buildpath('/asignaciones/control_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->asignaciones->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->asignaciones->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->asignaciones->write)
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
	    
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
	    
	    $value=$object->$key;
	    
	    if($key == 'fk_asignacion' && $value == '') continue;
	    if($key == 'fk_control_reemplazo' && $value == '') continue;
	    
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
	//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
	if ($object->fk_asignacion){
	    $asignacion = new Asignacion($db);
	    $asignacion->fetch($object->fk_asignacion);
	    
	    print '<tr><td class="titlefield">';
	    print "Asignacion relacionada";
	    print '</td>';
	    print '<td>';
	    print $asignacion->getNomUrl(1, '', 0, '', 1);
	    print '</td></tr>';
    	
    	$asignacion->fields = dol_sort_array($asignacion->fields, 'position');
    	
    	foreach($asignacion->fields as $key => $val)
    	{
    	    // Discard if extrafield is a hidden field on form
    	    if (abs($val['visible']) != 1 && abs($val['visible']) != 4) continue;
    	    
    	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
    	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
    	    
    	    $value=$asignacion->$key;
    	    
    	    if($key == 'fk_asignacion') continue;
    	    
    	    print '<tr><td';
    	    print ' class="titlefield fieldname_'.$key;
    	    //if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
    	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
    	    print '">';
    	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
    	    else print $langs->trans($val['label']);
    	    print '</td>';
    	    print '<td>';
    	    if ($val['type'] == 'datetime'){
    	        $value = dol_print_date($value, 'dayhour');
    	        print substr($value, 10);
    	    }else{
    	        print $asignacion->showOutputField($val, $key, $value, '', '', '', 0);
    	    }
    	    print '</td>';
    	    print '</tr>';
    	}
	}elseif ($object->fk_control_reemplazo !=''){
	    
	    $controlreemplazado = new Control($db);
	    $controlreemplazado->fetch($object->fk_control_reemplazo);
	    
	    print '<tr><td class="titlefield">';
	    print "Control Reemplazado";
	    print '</td>';
	    print '<td>';
	    print $controlreemplazado->getNomUrl(1, '', 0, '', 1)." - ".$controlreemplazado->LibStatut($controlreemplazado->status,'5') ;
	    print '</td></tr>';
    	
	    $controlreemplazado->fields = dol_sort_array($controlreemplazado->fields, 'position');
    	
	    foreach($controlreemplazado->fields as $key => $val)
    	{
    	    if ($key != "timestartreal" && $key != "timeendreal"){
        	    // Discard if extrafield is a hidden field on form
        	    if (abs($val['visible']) != 1 && abs($val['visible']) != 4) continue;
        	    
        	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
        	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
        	    
        	    $value=$controlreemplazado->$key;
        	    
        	    print '<tr><td';
        	    print ' class="titlefield fieldname_'.$key;
        	    //if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
        	    if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
        	    print '">';
        	    if (! empty($val['help'])) print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
        	    else print $langs->trans($val['label']);
        	    print '</td>';
        	    print '<td>';
        	    print $controlreemplazado->showOutputField($val, $key, $value, '', '', '', 0);
        	    print '</td>';
        	    print '</tr>';
    	    }
    	}
    	
	}
	
	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	// Verifico si hay algun objeto que reemplaza
	$result=0;
	if ( $object->status == $object::STATUS_REFUSED){
    	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$object->table_element;
    	$sql.= " WHERE fk_control_reemplazo = ".$object->id;
    	$resql = $db->query($sql);
    	if ($resql)
    	{
    	    $row = $db->fetch_row($resql);
    	    // Test for avoid error -1
    	    if ($row[0] > 0) {
    	        $control = new Control($db);
    	        $result = $control->fetch($row[0]);
    	    }
    	}
	}
	if($action == "confirm_replace" || (!$result && $object->status == $object::STATUS_REFUSED)){
	    $backtopage = dol_buildpath('/asignaciones/control_card.php', 1).'?id=__ID__';
	    print "<br/>";
	    print load_fiche_titre("Reemplazo del Control");
	    
	    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	    print '<input type="hidden" name="action" value="add">';
	    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	    print '<input type="hidden" name="status" value="1">';
	    print '<input type="hidden" name="fk_control_reemplazo" value="'.$object->id.'">';
	    print '<input type="hidden" id="timestartday" name="timestartday" value="'.date('d',$object->timestart).'">';
	    print '<input type="hidden" id="timestartmonth" name="timestartmonth" value="'.date('m',$object->timestart).'">';
	    print '<input type="hidden" id="timestartyear" name="timestartyear" value="'.date('Y',$object->timestart).'">';
	    print '<input type="hidden" name="timestarthour" value="'.date('H',$object->timestart).'">';
	    print '<input type="hidden" name="timestartmin" value="'.date('i',$object->timestart).'">';
	    print '<input type="hidden" name="timestartsec" value="'.date('s',$object->timestart).'">';
	    print '<input type="hidden" id="timeendday" name="timeendday" value="'.date('d',$object->timeend).'">';
	    print '<input type="hidden" id="timeendmonth" name="timeendmonth" value="'.date('m',$object->timeend).'">';
	    print '<input type="hidden" id="timeendyear" name="timeendyear" value="'.date('Y',$object->timeend).'">';
	    print '<input type="hidden" name="timeendhour" value="'.date('H',$object->timeend).'">';
	    print '<input type="hidden" name="timeendmin" value="'.date('i',$object->timeend).'">';
	    print '<input type="hidden" name="timeendsec" value="'.date('s',$object->timeend).'">';
	    print '<input type="hidden" name="fk_soc" value="'.$object->fk_soc.'">';
	    
	    
	    dol_fiche_head(array(), '');
	    
	    print '<table class="border centpercent">'."\n";
    
	    
	    // Common attributes
	    $object->fields = dol_sort_array($object->fields, 'position');
	    //$object->fields['timestart']['noteditable']=1;
	    //$object->fields['timeend']['noteditable']=1;   
	    $object->fields['fk_soc']['noteditable']=1;
	    $object->fields['timestartreal']['visible']=0;
	    $object->fields['timeendreal']['visible']=0;
	    $object->fields['fk_asignacion']['visible']=0;
	    $object->fields['fk_control_reemplazo']['visible']=0;
	    
	    
	    foreach($object->fields as $key => $val)
	    {
	        // Discard if extrafield is a hidden field on form
	        if (abs($val['visible']) != 1) continue;
	        
	        if($key == "fk_asignacion") continue;
	        
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
	        if ($key=="fk_user") {
	            $value='';
	            $output = "<!-- JS CODE TO ENABLE select2 for id = fk_user -->
                          <script>
                        	$(document).ready(function () {
                        		$('#fk_user').select2({
                        		    dir: 'ltr',
                        			width: 'resolve',		/* off or resolve */
                					minimumInputLength: 0,
                					language: select2arrayoflanguage,
                    				containerCssClass: ':all:',					
                					templateResult: function (data, container) {	/* Format visible output into combo list */
                	 					/* Code to add class of origin OPTION propagated to the new select2 <li> tag */
                						if (data.element) { $(container).addClass($(data.element).attr('class')); }
                					    //console.log(data.html);
                						if ($(data.element).attr('data-html') != undefined) return htmlEntityDecodeJs($(data.element).attr('data-html'));		// If property html set, we decode html entities and use this
                					    return data.text;
                					},
                					templateSelection: function (selection) {		/* Format visible output of selected value */
                						return selection.text;
                					},
                					escapeMarkup: function(markup) {
                						return markup;
                					},
                					dropdownCssClass: 'ui-dialog'
                				});
                        });
                        </script>";
	            $output .= '<select id="fk_user" class="flat" name="fk_user" tabindex="-1" aria-hidden="true">
                            <option value="-1">&nbsp;</option>';
	            
	            $inicio = date("Y-m-d H:i:s",$object->timestart);
	            $fin = date("Y-m-d H:i:s",$object->timeend);
	            
	            $sql = "SELECT users.rowid, users.lastname, users.firstname ";   
                $sql .= "FROM llx_user as users ";
                $sql .= "WHERE users.entity IN (0,1) AND users.rowid not in (SELECT ctrl.fk_user ";
				$sql .= "FROM llx_asignaciones_control as ctrl ";
				$sql .= "WHERE '".$inicio."' between ctrl.timestart and ctrl.timeend) ";
                $sql .= "UNION ";
                $sql .= "SELECT users.rowid, users.lastname, users.firstname ";   
                $sql .= "FROM llx_user as users ";
                $sql .= "WHERE users.entity IN (0,1) AND users.rowid not in (SELECT ctrl.fk_user ";
				$sql .= "FROM llx_asignaciones_control as ctrl ";
				$sql .= "WHERE '".$fin."' between ctrl.timestart and ctrl.timeend) ";
                $sql .= "UNION ";
                $sql .= "SELECT users.rowid, users.lastname, users.firstname ";  
                $sql .= "FROM llx_user as users  ";
                $sql .= "WHERE users.entity IN (0,1) AND users.rowid not in (SELECT ctrl.fk_user ";
				$sql .= "FROM llx_asignaciones_control as ctrl ";
				$sql .= "WHERE '".$inicio."' < ctrl.timeend and '".$fin."' > ctrl.timestart) ";
	            $sql .= "ORDER BY lastname ASC, firstname";
	            $resql = $db->query($sql);
	            if ($resql)
	            {
	            
	                $num = $db->num_rows($resql);
	                $i = 0;
	                if ($num)
	                {
	                    while ($i < $num)
	                    {
	                        $obj = $db->fetch_object($resql);
	                        $user = new User($db);
	                        $user->fetch($obj->rowid);
	                        $output.= '<option value="'.$obj->rowid.'">'.$user->lastname.' - '.$user->firstname.'</option>';
	                        $i++;
	                        $output.="\n";
	                    }
	                }
	            }
	            
	            $output .='</select>';
	            print $output;
	            
	        }
	        elseif ($key=="ref"){
	            $value='(PROV)';
	            print $object->showInputField($val, $key, $value, '', '', '', 0);
	            
	        }
	        else{
	            $value = $object->$key;
	            if($val['noteditable']){
	                print $object->showOutputField($val, $key, $value, '', '', '', 0);
	            }else{
	                print $object->showInputField($val, $key, $value, '', '', '', 0);
	            }
	        }
	        
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
	
	if($result){
	    $reemplazo = new Control ($db);
	    $reemplazo->fetch($result);
	    print "<h4>Reemplazado por: ";
	    print $reemplazo->getNomUrl('1', '', 0, '', 1)."</h4>";
	    //print $object->info($result);
	}
	
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
    	    // Send
            //print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>'."\n";

    	    if ($object->status == $object::STATUS_VALIDATED OR $object->status == $object::STATUS_TOREFUSE)
    	    {
    	        // Validate
    	        if ($object->status == $object::STATUS_VALIDATED){
        	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid"';
        	        print '>'.$langs->trans('Validate').'</a></div>';
    	        }
    	        if (!$object->fk_control_reemplazo){
    	        // Replace
        	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=replace"';
        	        print '>Reemplazar</a></div>';
    	        }
                // Modify
                if (! empty($user->rights->asignaciones->write))
        		{
        			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
        		}
        		else
        		{
        			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
        		}
        		
        		// Delete (need delete permission, or if draft, just need create/modify permission)
        		if (! empty($user->rights->asignaciones->delete) || (! empty($object->fields['status']) && $object->status == $object::STATUS_DRAFT && ! empty($user->rights->asignaciones->write)))
        		{
        		    print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>'."\n";
        		}
        		else
        		{
        		    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
        		}
    	    }
    	    if ($object->status == $object::STATUS_APPROVED)
    	    {
    	        // Genera Novedad
    	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=rrhh_news"';
    	        print '>Generar Novedad RH</a></div>';
    	        // Genera Ticker
    	        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=ticket"';
    	        print '>Generar Ticket</a></div>';
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

	    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.dol_buildpath('/asignaciones/control_agenda.php', 1).'?id='.$object->id.'">';
	    $morehtmlright.= $langs->trans("SeeAll");
	    $morehtmlright.= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    //$somethingshown = $formactions->showactions($object, 'control', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

	    print '</div></div></div>';
	}
}

// End of page

llxFooter();
$db->close();
