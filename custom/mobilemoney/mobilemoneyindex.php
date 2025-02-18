<?php
// Load Dolibarr environment 
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page 
$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));

// Get parameters 
$action = GETPOST('action', 'aZ09');

llxHeader("", $langs->trans("Mobile Money Payment"), '', '', 0, 0, '', '', '', 'mod-mobilemoney page-index');

print load_fiche_titre($langs->trans("Mobile Money Payment"), '', 'payment.png');

// Formulaire de saisie des informations 
print '<div class="d-flex justify-content-center align-items-center min-vh-100">';
print '<div class="card shadow-sm p-4" style="max-width: 500px; width: 100%; border-radius: 12px; border: 1px solid #ddd;">';

print '<div class="card-header bg-primary text-white text-center p-3" style="border-radius: 8px 8px 0 0;">';
print '<h4 class="mb-0">'.$langs->trans("Payment Entry").'</h4>';
print '</div>';

print '<div class="card-body">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="show_invoices">'; // Action pour afficher les factures
print '<input type="hidden" name="token" value="'.newToken().'">'; // CSRF Token

// Amount 
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Amount").'</label>';
print '<input type="text" name="amount" class="form-control rounded-3 border border-secondary p-2" required pattern="^\d+(\.\d{1,2})?$" title="Veuillez entrer un montant valide (ex: 120000.00)">';
print '</div>';

// Submit Button 
print '<button type="submit" class="btn btn-success w-100 p-2 rounded-3">';
print '<i class="fas fa-paper-plane"></i> '.$langs->trans("Show Invoices");
print '</button>';

print '</form>';
print '</div>'; // Fin card-body 
print '</div>'; // Fin card 
print '</div>'; // Fin container

// Traitement des soumissions pour afficher les factures
if ($action == 'show_invoices') {
    $amount = price2num(GETPOST('amount', 'alphanohtml'));
    if (!is_numeric($amount) || $amount <= 0) {
        die("Montant invalide !");
    }

    // Log pour débogage
    error_log("Montant saisi: " . $amount);
    error_log("Valeur après price2num: " . $amount);

    // Requête SQL avec tolérance sur le montant
    $sql_invoices = "SELECT f.rowid, f.ref, f.total_ttc, f.fk_statut, s.nom AS client_name 
                     FROM ".MAIN_DB_PREFIX."facture f 
                     JOIN ".MAIN_DB_PREFIX."societe s ON f.fk_soc = s.rowid 
                     WHERE f.fk_statut = 1 
                     AND f.total_ttc BETWEEN ".($amount - 0.05)." AND ".($amount + 0.05);

    // Log pour débogage
    error_log("Requête SQL: " . $sql_invoices);

    $resql_invoices = $db->query($sql_invoices);

    if (!$resql_invoices) {
        error_log("Erreur SQL: " . $db->lasterror());
    }

    if ($resql_invoices && $db->num_rows($resql_invoices) > 0) {
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
        print '<h3>Factures avec Statut 1 et Montant Saisi</h3>';
        print '<table class="table">';
        print '<thead><tr><th>Sélection</th><th>Référence</th><th>Client</th><th>Montant TTC</th><th>Statut</th></tr></thead>';
        print '<tbody>';

        while ($invoice = $db->fetch_object($resql_invoices)) {
            print '<tr>';
            print '<td><input type="checkbox" name="selected_invoices[]" value="'.$invoice->rowid.'"></td>';
            print '<td>'.$invoice->ref.'</td>';
            print '<td>'.$invoice->client_name.'</td>';
            print '<td>'.price($invoice->total_ttc).'</td>';
            print '<td>'.$invoice->fk_statut.'</td>';
            print '</tr>';
        }

        print '</tbody>';
        print '</table>';
        print '<div class="mb-3">';
        print '<label class="form-label">Code de transfert</label>';
        print '<input type="text" name="transfer_code" class="form-control" required>';
        print '</div>';
        print '<button type="submit" class="btn btn-primary" name="action" value="record_payment">Enregistrer le paiement</button>';
        print '</form>';
    } else {
        print '<div class="alert alert-info mt-3 text-center">'.$langs->trans("Aucune facture trouvée avec le statut 1 et le montant saisi").'</div>';
    }
}

// Traitement de l'enregistrement du paiement
if ($action == 'record_payment') {
    $selected_invoices = GETPOST('selected_invoices', 'array');
    $transfer_code = GETPOST('transfer_code', 'alphanohtml');

    $sql_check_code = "SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."mobilemoney_payments WHERE transfer_code = '".$db->escape($transfer_code)."'";
    $res_check_code = $db->query($sql_check_code);
    $row_check_code = $db->fetch_array($res_check_code);

    if ($row_check_code[0] > 0) {
        print '<div class="alert alert-danger">Ce code de transfert a déjà été utilisé et ne peut être réutilisé.</div>';
    } else {
        foreach ($selected_invoices as $invoice_id) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mobilemoney_payments (amount, transfer_code, invoice_number, client_name, status, date) 
                    SELECT total_ttc, '".$db->escape($transfer_code)."', ref, s.nom, 1, NOW()
                    FROM ".MAIN_DB_PREFIX."facture f
                    JOIN ".MAIN_DB_PREFIX."societe s ON f.fk_soc = s.rowid
                    WHERE f.rowid = ".$invoice_id;
            $db->query($sql);
        }
        print '<div class="alert alert-success">Paiement enregistré avec succès.</div>';
    }
}

llxFooter();
?>