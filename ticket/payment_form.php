<?php
/* Copyright (C) 2021-2025 SuperAdmin */

/**
 * \file htdocs/ticket/payment_form.php
 * \ingroup ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';

$langs->loadLangs(array('admin', 'bills', 'ticket'));

// Vérifier les droits d'accès
if (!$user->hasRight('ticket', 'read')) {
    accessforbidden('Not enough permissions');
}

$action = GETPOST('action', 'aZ09');

llxHeader("", $langs->trans("Mobile Money Payment Form"), '', '', 0, 0, '', '', '', 'mod-ticket page-paymentform');
print load_fiche_titre($langs->trans("Mobile Money Payment Form"), '', 'payment.png');

print '<div class="d-flex justify-content-center align-items-center min-vh-100">';
print '<div class="card shadow-sm p-4" style="max-width: 500px; width: 100%; border-radius: 12px; border: 1px solid #ddd;">';
print '<div class="card-header bg-primary text-white text-center p-3" style="border-radius: 8px 8px 0 0;">';
print '<h4 class="mb-0">'.$langs->trans("Enter Payment Information").'</h4>';
print '</div>';
print '<div class="card-body">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="submit_payment_info">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Client Name (Optionnel)
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Client Name").' (Optional)</label>';
print '<input type="text" name="client_name" class="form-control rounded-3 border border-secondary p-2">';
print '</div>';

// Transfer Code (Requis)
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Transfer Code").'</label>';
print '<input type="text" name="transfer_code" class="form-control rounded-3 border border-secondary p-2" required>';
print '</div>';

// Invoice Number (Requis)
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Invoice Number").'</label>';
print '<input type="text" name="invoice_number" class="form-control rounded-3 border border-secondary p-2" required>';
print '</div>';

// Bouton de soumission
print '<button type="submit" class="btn btn-success w-100 p-2 rounded-3">';
print '<i class="fas fa-paper-plane"></i> Submit ';
print '</button>';
print '</form>';
print '</div>'; // Fin card-body
print '</div>'; // Fin card
print '</div>'; // Fin container

// Traitement des soumissions
if ($action == 'submit_payment_info') {
    // Vérification du token CSRF
    if (empty($_REQUEST['token']) || $_REQUEST['token'] !== $_SESSION['newtoken']) {
        print '<div class="alert alert-danger mt-3 text-center">'.$langs->trans("CSRFTokenInvalid").'</div>';
        exit;
    }

    $client_name = GETPOST('client_name', 'alpha');
    $transfer_code = GETPOST('transfer_code', 'alpha');
    $invoice_number = GETPOST('invoice_number', 'alpha');

    // Vérifier si la facture existe
    $sql_invoice = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE ref = '$invoice_number' AND fk_statut = 1"; // 1 = "unpaid"
    $resql_invoice = $db->query($sql_invoice);
    if ($resql_invoice && $db->num_rows($resql_invoice) > 0) {
        $invoice = $db->fetch_object($resql_invoice);

        // Vérifier si le paiement est déjà en "pending"
        $sql_payment = "SELECT * FROM ".MAIN_DB_PREFIX."mobilemoney_payments WHERE invoice_number = '$invoice_number' AND transfer_code = '$transfer_code' AND status = 'pending'";
        $resql_payment = $db->query($sql_payment);
        if ($resql_payment && $db->num_rows($resql_payment) > 0) {
            print '<div class="alert alert-info mt-3 text-center"><i class="fas fa-info-circle"></i> '.$langs->trans("Payment Recorded").'</div>';
        } else {
            // Enregistrement des informations en attente
            $sql_insert_payment = "INSERT INTO ".MAIN_DB_PREFIX."mobilemoney_payments (amount, transfer_code, invoice_number, client_name, status, date) SELECT total_ttc, '".$db->escape($transfer_code)."', '".$db->escape($invoice_number)."', '".$db->escape($client_name)."', 'pending', NOW() FROM ".MAIN_DB_PREFIX."facture WHERE ref = '".$db->escape($invoice_number)."' AND fk_statut = 1";
            $db->query($sql_insert_payment);
            print '<div class="alert alert-info mt-3 text-center"><i class="fas fa-info-circle"></i> '.$langs->trans("Payment Recorded").'</div>';
        }
    } else {
        print '<div class="alert alert-warning mt-3 text-center"><i class="fas fa-times-circle"></i> '.$langs->trans("Invoice Not Found").'</div>';
    }
}
?>
