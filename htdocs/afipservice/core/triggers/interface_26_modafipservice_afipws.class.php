<?php
/**
 * Interfaz para el módulo AFIPWS
 * Maneja los triggers para la validación de facturas con AFIP
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

class InterfaceAfipws extends DolibarrTriggers 
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     * 
     * @param DoliDB $db Database handler
     */
    public function __construct($db) 
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "finacial";
        $this->description = "Triggers of this module allows valid bill to AFIPWS";
        $this->version = "3.0.1";
        $this->picto = "afipservice@afipservice";
    }

    /**
     * Retorna el nombre del disparador
     * 
     * @return string Nombre del disparador
     */
    public function getName() 
    {
        return $this->name;
    }

    /**
     * Retorna la descripción del disparador
     * 
     * @return string Descripción del disparador
     */
    public function getDesc() 
    {
        return $this->description;
    }

    /**
     * Ejecuta el disparador
     * 
     * @param string $action Acción que activó el disparador
     * @param object $object Objeto afectado por el disparador
     * @param User $user Usuario que ejecuta la acción
     * @param Translate $langs Objeto para traducciones
     * @param Conf $conf Configuración
     * @return int Resultado de la ejecución
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) 
    {
        // Verificar si el módulo está habilitado
        if (empty($conf->afipservice->enabled)) {
            return 0;
        }

        switch ($action) {
            case "BILL_VALIDATE":
                if ($object->thirdparty->country_code == "AR" || $object->thirdparty->country_code == '') {
                    if (in_array($object->model_pdf, ['afipservice_fe', 'afipws_fe', 'fe']) && $object->mode_reglement_code != "ECO") {
                        dol_syslog("Trigger '" . $this->name . "' for action '{$action}' launched by " . __FILE__ . ". id=" . $object->id . " model_pdf=" . $object->model_pdf);
                        include_once DOL_DOCUMENT_ROOT . '/afipservice/lib/wsfev1.lib.php';
                        return wsfev1($object);
                    }
                } else {
                    dol_syslog("Trigger '" . $this->name . "' for action '{$action}' launched by " . __FILE__ . ". id=" . $object->id . " (export)");
                    include_once DOL_DOCUMENT_ROOT . '/afipservice/lib/wsfexv1.lib.php';
                    return wsfexv1($object);
                }

                dol_syslog("Trigger '" . $this->name . "' for action '{$action}' launched by " . __FILE__ . ". id=" . $object->id . " (skipped - model_pdf=" . $object->model_pdf . ")");
                break;

            default:
                dol_syslog("Trigger '" . $this->name . "' for action '{$action}' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }
        
        return 0;
    }
}