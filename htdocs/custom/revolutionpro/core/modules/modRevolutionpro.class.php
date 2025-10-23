<?php
/* Dolibarr Theme
 * Copyright (C) 2020  DolibarrStore.com
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
 * 	\defgroup	Revolutionpro	Revolutionpro module
 * 	\brief		Revolutionpro module descriptor.
 * 	\file		core/modules/modRevolutionpro.class.php
 * 	\ingroup	revolutionpro
 * 	\brief		Description and activation file for module Revolutionpro
 */

include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Description and activation class for module Revolutionpro
 */
class modRevolutionpro extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$this->numero = 940326081;
		$this->rights_class = 'revolutionpro';

		$this->family = "DolibarrStore Themes";
		$this->editor_name = 'Dolibarr Store';
		$this->editor_url = 'https://www.dolibarrstore.com';
		$this->module_position = '100';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Thème de Dolibarr - Affichage responsive";
		$this->version = '18.4';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->special = 0;
		$this->picto = 'thumb@revolutionpro';

		$this->module_parts = array(
			'menus' => 1,
			'css' => array('data'=>array(
				'revolutionpro/css/revolutionpro.css.php'
				,'revolutionpro/theme/global/css/login-v2.css'
				,'revolutionpro/theme/custom/custom.min.css'
				,'revolutionpro/theme/assets/css/site.min.css'
			)
				), 
			'js' => array('data'=>array(
				'revolutionpro/js/revolutionpro.js.php')
			), 
			'hooks' => array('data'=>array('toprightmenu','main','all') ), 
		);

		$this->dirs = array();

		$this->config_page_url = array();
		$this->config_page_url = array("setup.php@revolutionpro");

		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(5, 3);
		$this->need_dolibarr_version = array(4, 0);
		$this->langfiles = array("revolutionpro@revolutionpro");

		

		// Dictionaries
		if (! isset($conf->revolutionpro->enabled)) {
			$conf->revolutionpro = new stdClass();
			$conf->revolutionpro->enabled = 0;
		}
		$this->dictionaries = array();
		$this->boxes = array(); // Boxes list

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 2;

		$this->rights[$r][0] = $this->numero+$r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'revolutionprochangelanguage';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'changelang';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;


		// Exports
		$r = 0;

		// Constants
		$this->const = array ();
		$r = 0;
		
		$r ++;
		$this->const [$r] [0] = "MAIN_FORCETHEME";	// name
		$this->const [$r] [1] = "chaine";			// type
		$this->const [$r] [2] = 'eldy';				// def value
		$this->const [$r] [3] = '';					// note
		$this->const [$r] [4] = 0;					// visible
		$this->const [$r] [5] = $conf->entity;		// entity
		
		$r ++;
		$this->const [$r] [0] = "MAIN_MENU_STANDARD_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'revolutionpro_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = $conf->entity;
		
		$r ++;
		$this->const [$r] [0] = "MAIN_MENUFRONT_STANDARD_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'revolutionpro_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = $conf->entity;
		
		$r ++;
		$this->const [$r] [0] = "MAIN_MENU_SMARTPHONE_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'revolutionpro_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = $conf->entity;
		
		$r ++;
		$this->const [$r] [0] = "MAIN_MENUFRONT_SMARTPHONE_FORCED";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = 'revolutionpro_menu.php';
		$this->const [$r] [3] = '';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = $conf->entity;
		
		$r ++;
		$this->const [$r] [0] = "DOL_VERSION";
		$this->const [$r] [1] = "chaine";
		$this->const [$r] [2] = '';
		$this->const [$r] [3] = 'Dolibarr version';
		$this->const [$r] [4] = 0;
		$this->const [$r] [5] = $conf->entity;

		if (dolibarr_get_const($this->db,'REVOLUTIONPRO_MODULES_ID',0) == 1)
		$this->hidden = true;

	}



	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $langs,$conf;
		$sql = array();
		$langs->load('revolutionpro@revolutionpro');

		$ardolv = DOL_VERSION;
     	$ardolv = explode(".", $ardolv);
	    $dolvs = $ardolv[0];

	    if ( $dolvs < 7 )
	        die($langs->trans('ContactAdministratorToGiveVersionCompatible').' - '.DOL_VERSION);
	    

		dolibarr_set_const($this->db,'MAIN_MENU_STANDARD_FORCED','revolutionpro_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENUFRONT_STANDARD_FORCED','revolutionpro_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENU_SMARTPHONE_FORCED','revolutionpro_menu.php','chaine',0,'',$conf->entity);
		dolibarr_set_const($this->db,'MAIN_MENUFRONT_SMARTPHONE_FORCED','revolutionpro_menu.php','chaine',0,'',$conf->entity);

		$arraythems = array("modBlueTheme","modOrangeTheme","modModernTheme");
		foreach ($arraythems as $value)
			if (in_array(strtolower(preg_replace('/^mod/','',$value)), $conf->modules)) $result=unActivateModule($value);


		// // Session FOR Menu
		// require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		// $extrafields=new ExtraFields($this->db);
		// $extrafields->addExtraField("revolutionpromenu", $langs->trans("MENU"), "int", "900", 2, "user", 0, 0, 0, '',0,'',0);
		// unset($_SESSION['revolutionproleftmenu']);

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		global $conf;
		$sql = array();


		dolibarr_del_const($this->db,'MAIN_MENU_STANDARD_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_STANDARD_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENU_SMARTPHONE_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MENUFRONT_SMARTPHONE_FORCED',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MODULE_REVOLUTIONPRO_CSS',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MODULE_REVOLUTIONPRO_HOOKS',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_MODULE_REVOLUTIONPRO_JS',$conf->entity);
		dolibarr_del_const($this->db,'MAIN_FORCETHEME',$conf->entity);

		$_sql = "DELETE FROM `".MAIN_DB_PREFIX."const` WHERE `value` like '%revolutionpro%' AND entity = 0";
		$resql = $this->db->query($_sql);


		// // Session FOR Menu
		// $sql1 = "DELETE FROM `".MAIN_DB_PREFIX."extrafields` WHERE `".MAIN_DB_PREFIX."extrafields`.`name` like '%revolutionpro%'";
		// $resql = $this->db->query($sql1);
		// unset($_SESSION['revolutionproleftmenu']);

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /revolutionpro/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		global $conf, $mysoc, $db;

		return 1;

	}

}
