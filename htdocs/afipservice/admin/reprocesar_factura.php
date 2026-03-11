<?php
/**
 * Script para reprocesar facturas electrónicas y obtener el CAE de AFIP.
 * Usar cuando facturas fueron confirmadas pero el CAE no fue obtenido.
 * ELIMINAR este archivo luego de usarlo.
 */

if (false === (@include '../../main.inc.php')) {
    require '../../../main.inc.php';
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/afipservice/lib/wsfev1.lib.php';

global $conf, $db, $user, $langs;

if (!$user->admin) {
    accessforbidden();
}

$facid  = GETPOST('facid', 'int');
$action = GETPOST('action', 'alpha');

llxHeader('', 'Reprocesar Facturas AFIP');

print '<style>
.ok   { background:#d4edda; border:1px solid #c3e6cb; padding:8px 12px; border-radius:4px; margin:8px 0; }
.err  { background:#f8d7da; border:1px solid #f5c6cb; padding:8px 12px; border-radius:4px; margin:8px 0; }
.warn { background:#fff3cd; border:1px solid #ffeeba; padding:8px 12px; border-radius:4px; margin:8px 0; }
table.sinCAE { border-collapse:collapse; width:100%; font-size:13px; }
table.sinCAE th, table.sinCAE td { border:1px solid #dee2e6; padding:5px 8px; }
table.sinCAE th { background:#f8f9fa; }
</style>';

print '<div class="fiche"><h1>Reprocesar Facturas Electrónicas AFIP</h1>';

// ── Acción: reprocesar una factura ──────────────────────────────────────────
if ($action == 'reprocesar' && $facid > 0) {

    $object = new Facture($db);
    $ret    = $object->fetch($facid);

    if ($ret <= 0) {
        print '<div class="err">No se encontró la factura con ID ' . $facid . '</div>';
    } elseif ($object->status != 1) {
        print '<div class="err">La factura <strong>' . $object->ref . '</strong> no está validada (estado: ' . $object->status . '). Solo se pueden reprocesar facturas validadas.</div>';
    } else {
        $object->fetch_thirdparty();
        $object->fetch_lines();

        // Simulamos el newref que se setea durante BILL_VALIDATE
        $object->newref = $object->ref;

        print '<p>Procesando factura <strong>' . $object->ref . '</strong> (ID: ' . $object->id . ', fecha: ' . dol_print_date($object->datef, 'day') . ')</p>';
        print '<p>Modelo: <strong>' . $object->model_pdf . '</strong></p>';

        if (!in_array($object->model_pdf, ['afipservice_fe', 'afipws_fe', 'fe'])) {
            print '<div class="warn">Esta factura no usa modelo de FE AFIP (modelo: ' . $object->model_pdf . '). No se puede reprocesar.</div>';
        } else {
            $result = wsfev1($object);

            if ($result >= 0) {
                print '<div class="ok"><strong>¡Éxito!</strong> CAE obtenido y guardado. Regenere el PDF de la factura.</div>';
            } else {
                print '<div class="err">Error al obtener el CAE. Verifique los mensajes de arriba (puede ser que AFIP rechace la fecha si es anterior a los últimos 5 días hábiles).</div>';
            }
        }
    }

    print '<br><a href="' . $_SERVER['PHP_SELF'] . '" class="button">Volver</a>';

// ── Vista principal ──────────────────────────────────────────────────────────
} else {

    // Facturas validadas sin CAE, ordenadas más reciente primero
    $sql = "SELECT f.rowid, f.ref, f.datef, f.model_pdf, s.nom as cliente"
         . " FROM " . MAIN_DB_PREFIX . "facture f"
         . " LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc"
         . " WHERE f.fk_statut = 1"
         . " AND f.model_pdf IN ('afipservice_fe','afipws_fe','fe')"
         . " AND f.rowid NOT IN (SELECT fk_facture FROM " . MAIN_DB_PREFIX . "wsfe WHERE fk_facture IS NOT NULL)"
         . " ORDER BY f.datef DESC, f.rowid DESC";

    $res = $db->query($sql);
    $rows = [];
    while ($obj = $db->fetch_object($res)) {
        $rows[] = $obj;
    }

    $total = count($rows);
    $hoy   = dol_now();
    // Límite orientativo: AFIP acepta fechas dentro de ~5 días hábiles
    $limite = $hoy - (7 * 24 * 3600); // 7 días calendario como margen

    print '<div class="warn"><strong>Atención:</strong> Se encontraron <strong>' . $total . '</strong> factura(s) confirmadas sin CAE de AFIP.<br>';
    print 'Las fechadas hace más de ~7 días probablemente sean rechazadas por AFIP. Las recientes pueden reprocessarse.</div>';

    // Formulario para ID manual
    print '<br><h2>Reprocesar una factura por ID</h2>';
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="reprocesar">';
    print 'ID de Factura: <input type="number" name="facid" value="' . (int)$facid . '" class="flat" style="width:100px" />';
    print ' <input type="submit" class="button" value="Reprocesar">';
    print '</form>';

    if ($total > 0) {
        print '<br><h2>Facturas sin CAE (' . $total . ')</h2>';
        print '<table class="sinCAE">';
        print '<tr><th>ID</th><th>Ref</th><th>Fecha</th><th>Cliente</th><th>Modelo</th><th>Estado</th><th>Acción</th></tr>';

        foreach ($rows as $row) {
            $esReciente = ($row->datef >= date('Y-m-d', $limite));
            $estado = $esReciente
                ? '<span style="color:green">✓ Reciente (reprocesable)</span>'
                : '<span style="color:#c00">✗ Antigua (AFIP puede rechazar)</span>';

            print '<tr>';
            print '<td>' . $row->rowid . '</td>';
            print '<td><a href="' . DOL_URL_ROOT . '/compta/facture/card.php?id=' . $row->rowid . '" target="_blank">' . $row->ref . '</a></td>';
            print '<td>' . dol_print_date($db->jdate($row->datef), 'day') . '</td>';
            print '<td>' . $row->cliente . '</td>';
            print '<td>' . $row->model_pdf . '</td>';
            print '<td>' . $estado . '</td>';
            print '<td>';
            if ($esReciente) {
                print '<form method="POST" style="display:inline">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="reprocesar">';
                print '<input type="hidden" name="facid" value="' . $row->rowid . '">';
                print '<button type="submit" class="button smallpadding">Reprocesar</button>';
                print '</form>';
            } else {
                print '-';
            }
            print '</td>';
            print '</tr>';
        }
        print '</table>';
    }
}

print '</div>';

llxFooter();
$db->close();
