<?php
/* Copyright (C) 2022 Paula Verónica Priano <paula.priano@primetec.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    hooksprimetec/class/actions_hooksprimetec.class.php
 * \ingroup hooksprimetec
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsHooksPrimetec
 */
class ActionsHooksPrimetec
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/* Add here any other hooked methods... */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs, $db;
	    
	    $error = 0; // Error counter
	    
	    /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('thirdpartycard')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
	        $extrafields = new ExtraFields($db);
	        
	        // fetch optionals attributes and labels
	        $extrafields->fetch_name_optionals_label($object->table_element);
	        
            print '
            	<script type="text/javascript">
            
                    jQuery(document).ready(function() {
            			agregabotones();
                        collapseall();
            		});
            
            		function collapseall() {
            			$("[class^=\'trextrafields_collapse\']").hide();
            			$(".trextrafieldseparatorbuttonhide").hide();
            		}

            		function agregabotones() {
            			$(".trextrafieldseparator td").append("<a style=\'float:right;\' href=\'#\' class=\'trextrafieldseparatorbutton trextrafieldseparatorbuttonshow\'>Mostrar</a>");
            			$(".trextrafieldseparator td").append("<a style=\'float:right;\' href=\'#\' class=\'trextrafieldseparatorbutton trextrafieldseparatorbuttonhide\'>Ocultar</a>");
            			$(".trextrafieldseparatorbuttonhide").hide();
            		}		

                    $("body").on("click", ".trextrafieldseparatorbuttonshow", function(e) {
                        parent = $(this).parent().parent().attr("id");
                        $("#"+parent).nextUntil(".trextrafieldseparator").show();
                        $(this).hide();
                        $(this).siblings(".trextrafieldseparatorbuttonhide").show()
                        return false;
                    });
                    $("body").on("click", ".trextrafieldseparatorbuttonhide", function(e) {
                        parent = $(this).parent().parent().attr("id");
                        $("#"+parent).nextUntil(".trextrafieldseparator").hide();
                        $(this).hide();
                        $(this).siblings(".trextrafieldseparatorbuttonshow").show()
                        return false;
                    });

            	</script>
';
	        
	        return 0; // or return 1 to replace standard code*/
	    }
	    
	    if ($error){
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}
	
	
	
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs, $db;
	    
	    $error = 0; // Error counter
	    
	    /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('thirdpartycomm')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
	        $extrafields = new ExtraFields($db);
	        
	        // fetch optionals attributes and labels
	        $extrafields->fetch_name_optionals_label($object->table_element);
	        
	        print '
            	<script type="text/javascript">
	            
                    jQuery(document).ready(function() {
            			agregabotones();
                        collapseall();
            		});
	            
            		function collapseall() {
            			$("[class^=\'trextrafields_collapse\']").hide();
            			$(".trextrafieldseparatorbuttonhide").hide();
            		}
	            
            		function agregabotones() {
            			$(".trextrafieldseparator td").append("<a style=\'float:right;\' href=\'#\' class=\'trextrafieldseparatorbutton trextrafieldseparatorbuttonshow\'>Mostrar</a>");
            			$(".trextrafieldseparator td").append("<a style=\'float:right;\' href=\'#\' class=\'trextrafieldseparatorbutton trextrafieldseparatorbuttonhide\'>Ocultar</a>");
            			$(".trextrafieldseparatorbuttonhide").hide();
            		}
	            
                    $("body").on("click", ".trextrafieldseparatorbuttonshow", function(e) {
                        parent = $(this).parent().parent().attr("id");
                        $("#"+parent).nextUntil(".trextrafieldseparator").show();
                        $(this).hide();
                        $(this).siblings(".trextrafieldseparatorbuttonhide").show()
                        return false;
                    });
                    $("body").on("click", ".trextrafieldseparatorbuttonhide", function(e) {
                        parent = $(this).parent().parent().attr("id");
                        $("#"+parent).nextUntil(".trextrafieldseparator").hide();
                        $(this).hide();
                        $(this).siblings(".trextrafieldseparatorbuttonshow").show()
                        return false;
                    });
	            
            	</script>
';
	        
	        return 0; // or return 1 to replace standard code*/
	    }
	    
	    if ($error){
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}
	
}
