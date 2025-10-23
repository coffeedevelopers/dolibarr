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
 *   	\file       asignacion_card.php
 *		\ingroup    asignaciones
 *		\brief      Page to create/edit/view asignacion
 */

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
dol_include_once('/asignaciones/class/asignacion.class.php');
dol_include_once('/asignaciones/lib/asignaciones_asignacion.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("asignaciones@asignaciones","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'asignacioncard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object=new Asignacion($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->asignaciones->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('asignacioncard','globalcard'));     // Note that conf->hooks_modules contains array
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
    $backurlforlist = dol_buildpath('/asignaciones/asignacion_list.php', 1);
    if (empty($backtopage)) {
        if (empty($id)) $backtopage = $backurlforlist;
        else $backtopage = dol_buildpath('/asignaciones/asignacion_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
    }
    $triggermodname = 'ASIGNACIONES_ASIGNACION_MODIFY';	// Name of trigger action code to execute when we modify record

    // Actions cancel, add, update, delete or clone
    include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

    // Actions when linking object each other
    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Actions to send emails
    $trigger_name='ASIGNACION_SENTBYMAIL';
    $autocopy='MAIN_MAIL_AUTOCOPY_ASIGNACION_TO';
    $trackid='asignacion'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('', $langs->trans('Asignacion'), '');

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		$("#timestart").parent().hide();
		$("#timeend").parent().hide();
        $("#timestartday").val("01");
        $("#timestartmonth").val("01");
        $("#timestartyear").val("1970");
        $("#timeendday").val("01");
        $("#timeendmonth").val("01");
        $("#timeendyear").val("1970");
		$(".datenowlink").hide();
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
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Asignacion")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

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
	print load_fiche_titre($langs->trans("Asignacion"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

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
	$head = asignacionPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Asignacion"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteAsignacion'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
	    // Create an array for form
	    $index = 7;
	    $formquestion = array();
	    
	    for ($i = 0; $i < $index; $i++) {
	        
	        $filter = array(
	            'fk_user'=>$object->fk_user,
	            'fk_soc'=> $object->fk_soc,
				'timestart'=>date("Y-m-d H:i:s",$object->timestart),
				//'timeend'=>$object->timeend,
	            'weekday'=> $i,
	        );
	        
			$result = $object->fetchAll('','',1,0,$filter);
	        
	        if(!$result){
	            $formquestion[$i] = array('label'=> $object->getWeekday($i), 'name'=> $i,'type'=> 'checkbox','value'=> 0);
	        }else{
	            $formquestion[$i] = array('label'=> $object->getWeekday($i), 'name'=> $i,'type'=> 'checkbox','disabled'=>1,'value'=> 0);
	        }
	        
	    }
	    
	    /*$formquestion = array(
	     array('label'=> 'Lunes', 'name'=> '0','type'=> 'checkbox', 'value'=> ($object->weekday == 0)?'1':'0', 'disabled'=> 0),
	     array('label'=> 'Martes', 'name'=> '1','type'=> 'checkbox', 'value'=> ($object->weekday == 1)?'1':'0'),
	     array('label'=> 'Miercoles', 'name'=> '2','type'=> 'checkbox', 'value'=> ($object->weekday == 2)?'1':'0'),
	     array('label'=> 'Jueves', 'name'=> '3','type'=> 'checkbox', 'value'=> ($object->weekday == 3)?'1':'0'),
	     array('label'=> 'Viernes', 'name'=> '4','type'=> 'checkbox', 'value'=> ($object->weekday == 4)?'1':'0'),
	     array('label'=> 'Sabado', 'name'=> '5','type'=> 'checkbox', 'value'=> ($object->weekday == 5)?'1':'0'),
	     array('label'=> 'Domingo', 'name'=> '6','type'=> 'checkbox', 'value'=> ($object->weekday == 6)?'1':'0'),
	     
	     );
	     var_dump($formquestion);*/
	    
	    $mensaje = "Seleccione los dias que desea clonar. Los dias ya generados no se volveran a generar.<br>Esta seguro de que quieres clonar la asignacion?";
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans($mensaje, $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}
	
	// pre validate
	if ($action == 'valid') {
	    $error = 0;
	    // Poner como olbigatorios los campos y validar el form
	    if (!$error){
	        $text = "Confirma la validacion? ";
	        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, "Validar Asigancion", $text, 'confirm_validate', '', 0, 1);
	    }else{
	        setEventMessages('', $object->errors, 'errors');
	    }
	}
	
	// Confirm validate
	if ($action == 'confirm_validate' && $confirm == "yes") {
	    $object->status = $object::STATUS_VALIDATED;
	    //$object->ref = $object->getNextNumRef($object);
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
	$linkback = '<a href="' .dol_buildpath('/asignaciones/asignacion_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');
	
	foreach($object->fields as $key => $val)
	{
	    // Discard if extrafield is a hidden field on form
	    if (abs($val['visible']) != 1 && abs($val['visible']) != 4) continue;
	    
	    if (array_key_exists('enabled', $val) && isset($val['enabled']) && ! verifCond($val['enabled'])) continue;	// We don't want this field
	    if (in_array($key, array('ref','status'))) continue;	// Ref and status are already in dol_banner
	    
	    $value=$object->$key;
	    
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
	        print $object->showOutputField($val, $key, $value, '', '', '', 0);
	    }
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
	// END Common attributes

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

    		// Clone
    		if (! empty($user->rights->asignaciones->write))
    		{
    			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=clone&amp;object=order">' . $langs->trans("ToClone") . '</a></div>';
    		}

    		/*
    		if ($user->rights->asignaciones->write)
    		{
    			if ($object->status == 1)
    		 	{
    		 		print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=disable">'.$langs->trans("Disable").'</a>'."\n";
    		 	}
    		 	else
    		 	{
    		 		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Enable").'</a>'."\n";
    		 	}
    		}
    		*/

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
	    $filedir = $conf->asignaciones->dir_output . '/' . $objref;
	    $urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	    $genallowed = $user->rights->asignaciones->read;	// If you can read, you can build the PDF to read content
	    $delallowed = $user->rights->asignaciones->create;	// If you can create/edit, you can remove a file on card
	    print $formfile->showdocuments('asignaciones', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
		*/

	    // Show links to link elements
	    /*$linktoelem = $form->showLinkToObjectBlock($object, null, array('asignacion'));
	    $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


	    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.dol_buildpath('/asignaciones/asignacion_agenda.php', 1).'?id='.$object->id.'">';
	    $morehtmlright.= $langs->trans("SeeAll");
	    $morehtmlright.= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    $somethingshown = $formactions->showactions($object, 'asignacion', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

	    print '</div></div></div>';*/
	}

	//Select mail models is same action as presend
	/*
	 if (GETPOST('modelselected')) $action = 'presend';

	 // Presend form
	 $modelmail='inventory';
	 $defaulttopic='InformationMessage';
	 $diroutput = $conf->product->dir_output.'/inventory';
	 $trackid = 'stockinv'.$object->id;

	 include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	 */
}

// End of page
llxFooter();
$db->close();
