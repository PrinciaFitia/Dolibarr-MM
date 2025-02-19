<?php
// Charge l'environnement Dolibarr
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Charge les fichiers de traduction nécessaires à la page 
$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));

// Récupère les paramètres
$action = GETPOST('action', 'aZ09');
$amount = price2num(GETPOST('amount', 'alphanohtml')); // Initialisation correcte du montant

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

// Montant 
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Amount").'</label>';
print '<input type="text" name="amount" class="form-control rounded-3 border border-secondary p-2" required pattern="^\d+(\.\d{1,2})?$" title="Veuillez entrer un montant valide (ex: 120000.00)" value="'.($amount > 0 ? price($amount) : '').'">';
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
if ($action == 'show_invoices' && $amount > 0) {
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
        print '<div class="alert alert-info mt-3 text-center">'.$langs->trans("No unpaid invoice found with the entered amount.").'</div>';
    }
}

// Traitement du paiement
if ($action == 'record_payment') {
    $selected_invoices = GETPOST('selected_invoices', 'array');
    $transfer_code = GETPOST('transfer_code', 'alphanohtml');

    // Vérifier si le code de transfert existe déjà dans la base avec un statut 'pending'
    $sql_check_code = "SELECT * 
                       FROM ".MAIN_DB_PREFIX."mobilemoney_payments 
                       WHERE transfer_code = '".$db->escape($transfer_code)."'";

    $res_check_code = $db->query($sql_check_code);
    $row_check_code = $db->fetch_object($res_check_code);

    if ($row_check_code) {
        // Le code de transfert existe dans la base
        if ($row_check_code->status == 'pending') {
            // Si le statut est "pending", on met à jour le statut en "validated"
            $sql_update_payment = "UPDATE ".MAIN_DB_PREFIX."mobilemoney_payments 
                                   SET status = 'validated' 
                                   WHERE transfer_code = '".$db->escape($transfer_code)."'";

            $db->query($sql_update_payment);

            // Mise à jour de la facture avec le statut validé (fk_statut = 2)
            foreach ($selected_invoices as $invoice_id) {
                $sql_update_invoice = "UPDATE ".MAIN_DB_PREFIX."facture 
                                       SET fk_statut = 2 
                                       WHERE rowid = ".$invoice_id;

                $db->query($sql_update_invoice);
            }

            print '<div class="alert alert-success">The payment has been validated and the invoice has been updated.</div>';
        } else {
            // Si le statut n'est pas "pending", on affiche un message d'erreur
            print '<div class="alert alert-danger">The transfer code has already been validated or cannot be used.</div>';
        }
    } else {
        // Si le code de transfert n'existe pas, on enregistre un nouveau paiement
        foreach ($selected_invoices as $invoice_id) {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mobilemoney_payments (amount, transfer_code, invoice_number, client_name, status, date) 
                    SELECT total_ttc, '".$db->escape($transfer_code)."', ref, s.nom, 'pending', NOW()
                    FROM ".MAIN_DB_PREFIX."facture f
                    JOIN ".MAIN_DB_PREFIX."societe s ON f.fk_soc = s.rowid
                    WHERE f.rowid = ".$invoice_id;
            $db->query($sql);

            // Mise à jour de la facture avec le statut en attente (fk_statut = 1)
            $sql_update_invoice = "UPDATE ".MAIN_DB_PREFIX."facture 
                                   SET fk_statut = 1 
                                   WHERE rowid = ".$invoice_id;

            $db->query($sql_update_invoice);
        }
        print '<div class="alert alert-success">Payment successfully recorded and the invoice updated.</div>';
    }
}

llxFooter();
?>
