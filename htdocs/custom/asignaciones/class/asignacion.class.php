<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file        class/asignacion.class.php
 * \ingroup     asignaciones
 * \brief       This file is a CRUD class file for Asignacion (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Asignacion
 */
class Asignacion extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'asignacion';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'asignaciones_asignacion';

	/**
	 * @var int  Does asignacion support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does asignacion support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for asignacion. Must be the part after the 'object_' into object_asignacion.png
	 */
	public $picto = 'asignacion@asignaciones';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;
	const STATUS_TOREFUSE = 8;



	/**
	 *  'type' if the field format ('integer', 'integer:Class:pathtoclass', 'varchar(x)', 'double(24,8)', 'text', 'html', 'datetime', 'timestamp', 'float')
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>1, 'position'=>10, 'notnull'=>1, 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object", 'showoncombobox'=>'1',),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'help'=>"LinkToThirparty",),
		'fk_user' => array('type'=>'integer:User:user/class/user.class.php:0:statut:>:"0"', 'label'=>'User', 'enabled'=>1, 'visible'=>1, 'position'=>51, 'notnull'=>1,),
		'weekday' => array('type'=>'integer', 'label'=>'Día de la Semana', 'enabled'=>1, 'visible'=>1, 'position'=>52, 'notnull'=>1, 'arrayofkeyval'=>array('0'=>'Lunes', '1'=>'Martes', '2'=>'Miercoles', '3'=>'Jueves', '4'=>'Viernes', '5'=>'Sabado', '6'=>'Domingo', '7'=>'Feriado')),
		'timestart' => array('type'=>'datetime', 'label'=>'Hora de Inicio', 'enabled'=>1, 'visible'=>1, 'position'=>53, 'notnull'=>1,),
		'timeend' => array('type'=>'datetime', 'label'=>'Hora de Finalización', 'enabled'=>1, 'visible'=>1, 'position'=>54, 'notnull'=>1,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>-1, 'position'=>62, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'position'=>500, 'notnull'=>1,),
	    'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'position'=>501, 'notnull'=>0,),
	    'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'position'=>510, 'notnull'=>1, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'position'=>511, 'notnull'=>-1,),
	    'fk_region' => array('type'=>'integer:Region:custom/asignaciones/class/region.class.php', 'label'=>'Región', 'enabled'=>1, 'visible'=>1, 'position'=>40, 'notnull'=>1, 'index'=>1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>1000, 'notnull'=>-1,),
	    'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Borrador', '1'=>'Activo', '9'=>'Cancelado', '8'=>'A Reemplazar')),
	);
	public $rowid;
	public $ref;
	public $fk_soc;
	public $fk_user;
	public $weekday;
	public $timestart;
	public $timeend;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $fk_region;
	public $import_key;
	public $status;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'asignaciones_asignacionline';

	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_asignacion';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'Asignacionline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	//protected $childtables=array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	//protected $childtablesoncascade=array('asignaciones_asignaciondet');

	/**
	 * @var AsignacionLine[]     Array of subtable lines
	 */
	//public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

		// Unset fields that are disabled
		foreach($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach($this->fields as $key => $val)
		{
			if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
			{
				foreach($val['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields[$key]['arrayofkeyval'][$key2]=$langs->trans($val2);
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $db;
	    $usuario = new User($db);
	    $usuario->fetch($this->fk_user);
	    $this->ref = $usuario->firstname." ".$usuario->lastname." - ".$this->getWeekday($this->weekday);
	    
        $this->setDate();
    
	    if ($this->asignacionExists()) {
	        return -1;
	    }else{
	        return $this->createCommon($user, $notrigger);
	    }
	    
	    //return $this->createCommon($user, $notrigger);
	    
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
	    global $langs, $extrafields;
	    $error = 0;
	    
	    dol_syslog(__METHOD__, LOG_DEBUG);
	    
	    $object = new self($this->db);
	    
	    $i=0;
	    for ($i = 0; $i < 7; $i++) {
	        if($_GET[$i]=="on"){
	            
	            $this->db->begin();
	            
	            // Load source object
	            $result = $object->fetchCommon($fromid);
	            if ($result > 0 && !empty($object->table_element_line)) {
	                $object->fetchLines();
	            }
	            
	            // get lines so they will be clone
	            //foreach($this->lines as $line)
	            //	$line->fetch_optionals();
	            
	            // Reset some properties
	            unset($object->id);
	            unset($object->fk_user_creat);
	            unset($object->import_key);
	            
	            // Set some propertiees
	            $object->weekday = $i;
	            
	            // Clear fields
	            if (property_exists($object, 'ref')) {
	                $object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
	            }
	            if (property_exists($object, 'label')) {
	                $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
	            }
	            if (property_exists($object, 'status')) {
	                $object->status = self::STATUS_DRAFT;
	            }
	            if (property_exists($object, 'date_creation')) {
	                $object->date_creation = dol_now();
	            }
	            if (property_exists($object, 'date_modification')) {
	                $object->date_modification = null;
	            }
	            // ...
	            // Clear extrafields that are unique
	            if (is_array($object->array_options) && count($object->array_options) > 0) {
	                $extrafields->fetch_name_optionals_label($this->table_element);
	                foreach ($object->array_options as $key => $option) {
	                    $shortkey = preg_replace('/options_/', '', $key);
	                    if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
	                        //var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
	                        unset($object->array_options[$key]);
	                    }
	                }
	            }
	            
	            // Create clone
	            $object->context['createfromclone'] = 'createfromclone';
	            $result = $object->createCommon($user);
	            if ($result < 0) {
	                $error++;
	                $this->error = $object->error;
	                $this->errors = $object->errors;
	            }
	            
	            if (!$error) {
	                // copy internal contacts
	                if ($this->copy_linked_contact($object, 'internal') < 0) {
	                    $error++;
	                }
	            }
	            
	            if (!$error) {
	                // copy external contacts if same company
	                if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
	                    if ($this->copy_linked_contact($object, 'external') < 0) {
	                        $error++;
	                    }
	                }
	            }
	            
	            // End
	            if (!$error) {
	                $this->db->commit();
	            } else {
	                $this->db->rollback();
	                return -1;
	            }
	            
	        }
	    }
	    return $object;
	    unset($object->context['createfromclone']);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines=array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records=array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.rowid') {
					$sqlwhere[] = $key . '='. $value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key=='customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .=  ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
            $i = 0;
			while ($i < min($limit, $num))
			{
			    $obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
	    global $db;
	    $usuario = new User($db);
	    $usuario->fetch($this->fk_user);
	    $this->ref = $usuario->firstname." ".$usuario->lastname." - ".$this->getWeekday($this->weekday);
	    
	    $this->setDate();
	    
	    if ($this->asignacionExists()) {
	        return -1;
	    }else{
	        return $this->updateCommon($user, $notrigger);
	    }
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}
	

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0)
		{
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("Asignacion") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/asignaciones/asignacion_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink')
        {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
            if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowAsignacion");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

            /*
             $hookmanager->initHooks(array('asignaciondao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action,$hookmanager;
		$hookmanager->initHooks(array('asignaciondao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
    }

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelstatus))
		{
			global $langs;
			//$langs->load("asignaciones");
			$this->labelstatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelstatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelstatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelstatus[self::STATUS_TOREFUSE] = 'A Reemplazar';
		}

		if ($mode == 0)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 1)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 2)
		{
			return img_picto($this->labelstatus[$status], 'statut'.$status, '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 3)
		{
			return img_picto($this->labelstatus[$status], 'statut'.$status, '', false, 0, 0, '', 'valignmiddle');
		}
		elseif ($mode == 4)
		{
			return img_picto($this->labelstatus[$status], 'statut'.$status, '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 5)
		{
			return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status], 'statut'.$status, '', false, 0, 0, '', 'valignmiddle');
		}
		elseif ($mode == 6)
		{
			return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status], 'statut'.$status, '', false, 0, 0, '', 'valignmiddle');
		}
		
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
	    $this->lines=array();

	    $objectline = new AsignacionLine($this->db);
	    $result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_asignacion = '.$this->id));

	    if (is_numeric($result))
	    {
	        $this->error = $this->error;
	        $this->errors = $this->errors;
	        return $result;
	    }
	    else
	    {
	        $this->lines = $result;
	        return $this->lines;
	    }
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf,$langs;

		$langs->load("asignaciones@asignaciones");

		if (! dol_strlen($modele)) {

			$modele = 'standard';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->ASIGNACION_ADDON_PDF)) {
				$modele = $conf->global->ASIGNACION_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/asignaciones/doc/";

		//return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	//public function doScheduledJob($param1, $param2, ...)
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error='';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}
	
	public function getWeekday ($daynumber){
	    return $this->fields['weekday']['arrayofkeyval'][$daynumber];
	}

	public function asignacionExists (){
	    $rows = 0;
	    
	    $sql = "SELECT * FROM llx_asignaciones_asignacion ";
	    $sql .="WHERE '".date("Y-m-d H:i:s",$this->timestart+1)."' between llx_asignaciones_asignacion.timestart and llx_asignaciones_asignacion.timeend ";
	    $sql .="AND fk_user = '".$this->fk_user."' ";
	    
	    if($this->id) $sql .="AND rowid != '".$this->id."' ";
	    
	    $resql = $this->db->query($sql);
	    
	    $rows += $this->db->num_rows($resql);
	    
	    $sql = "SELECT * FROM llx_asignaciones_asignacion ";
	    $sql .="WHERE '".date("Y-m-d H:i:s",$this->timeend-1)."' between llx_asignaciones_asignacion.timestart and llx_asignaciones_asignacion.timeend ";
	    $sql .="AND fk_user = '".$this->fk_user."' ";
	    
	    if($this->id) $sql .="AND rowid != '".$this->id."' ";
	    
	    $resql = $this->db->query($sql);
	    
	    $rows += $this->db->num_rows($resql);

	    $sql = "SELECT * FROM llx_asignaciones_asignacion ";
	    $sql .="WHERE '".date("Y-m-d H:i:s",$this->timestart)."' < llx_asignaciones_asignacion.timestart ";
	    $sql .="AND  '".date("Y-m-d H:i:s",$this->timeend)."' > llx_asignaciones_asignacion.timeend ";
	    $sql .="AND fk_user = '".$this->fk_user."' ";
	    
	    if($this->id) $sql .="AND rowid != '".$this->id."' ";
	    
	    $resql = $this->db->query($sql);
	    
	    $rows += $this->db->num_rows($resql);
	    
	    if ($rows!="0") {
	        $this->errors[] = 'El usuario seleccionado ya tiene tiempo asignado para el horario seleccionado';
	        dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
	        
	        return 1;
	    }else{
	        return 0;
	    }
	}
	
	public function setDate (){
	    if($this->timestart > (24*60*60)){
	        $dias = date("d",$this->timestart);
	        $this->timestart = $this->timestart - ($dias*24*60*60);
	    }
	    if($this->timeend > (24*60*60)){
	        $dias = date("d",$this->timeend);
	        $this->timeend = $this->timeend - ($dias*24*60*60);
	    }
	    $this->timestart = $this->timestart + ($this->weekday*24*60*60);
	    $this->timeend = $this->timeend + ($this->weekday*24*60*60);
	    
	    if($this->timeend < $this->timestart){
	          $this->timeend = $this->timeend + (24*60*60);
	          $this->errors[] = 'La Fecha de inicio es mayor a la Fecha de Finalizacion. Se establecer� como el dia siguiente la Fecha de Finalizacion';
	          setEventMessages('', $this->errors, 'warnings');
	          return 1;
	    }else{
	        return 1;
	    }
	    
	}
	public function validate($user, $notrigger = 0)
	{
	    global $conf, $langs, $db;
	    	    
	    $error = 0;
	    
	    // Protection
	    if ($this->status == self::STATUS_VALIDATED) {
	        dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
	        return 0;
	    }
	    
	    $now = dol_now();
	    
	    $this->db->begin();
	    
	    // Define new ref
	    if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
	        $usuario = new User($db);
	        $usuario->fetch($this->fk_user);
	        $num = $this->ref = $usuario->firstname." ".$usuario->lastname." - ".$this->getWeekday($this->weekday);
	    } else {
	        $num = $this->ref;
	    }
	    $this->newref = $num;
	    
	    if (!empty($num)) {
	        // Validate
	        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
	        $sql .= " SET ref = '".$this->db->escape($num)."',";
	        $sql .= " status = ".self::STATUS_VALIDATED;
	        if (!empty($this->fields['date_validation'])) {
	            $sql .= ", date_validation = '".$this->db->idate($now)."'";
	        }
	        if (!empty($this->fields['fk_user_valid'])) {
	            $sql .= ", fk_user_valid = ".((int) $user->id);
	        }
	        $sql .= " WHERE rowid = ".((int) $this->id);
	        
	        dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
	        $resql = $this->db->query($sql);
	        if (!$resql) {
	            dol_print_error($this->db);
	            $this->error = $this->db->lasterror();
	            $error++;
	        }
	        
	        if (!$error && !$notrigger) {
	            // Call trigger
	            $result = $this->call_trigger('ASIGNACION_VALIDATE', $user);
	            if ($result < 0) {
	                $error++;
	            }
	            // End call triggers
	        }
	    }
	    
	    // Set new ref and current status
	    if (!$error) {
	        $this->ref = $num;
	        $this->status = self::STATUS_VALIDATED;
	    }
	    
	    if (!$error) {
	        $this->db->commit();
	        return 1;
	    } else {
	        $this->db->rollback();
	        return -1;
	    }
	}
}

/**
 * Class AsignacionLine. You can also remove this and generate a CRUD class for lines objects.
 */
class AsignacionLine
{
	// To complete with content of an object AsignacionLine
	// We should have a field rowid, fk_asignacion and position
}
