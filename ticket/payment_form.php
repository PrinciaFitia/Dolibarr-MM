<?php
/* Copyright (C) 2021-2025  SuperAdmin
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/ticket/payment_form.php
 *    \ingroup    ticket
 */

// Load Dolibarr environment
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'bills', 'ticket'));


// Get parameters
$action = GETPOST('action', 'aZ09');

llxHeader("", $langs->trans("Mobile Money Payment Form"), '', '', 0, 0, '', '', '', 'mod-ticket page-paymentform');

print load_fiche_titre($langs->trans("Mobile Money Payment Form"), '', 'payment.png');

// Formulaire de saisie des informations par le client
print '<div class="container">';
print '<div class="form-section">';
print '<h3>'.$langs->trans("Payment Information Entry").'</h3>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" class="form-inline">';
print '<input type="hidden" name="action" value="submit_payment_info">';
print '<input type="hidden" name="token" value="'.newToken().'">'; // Ajoutez cette ligne
print '<div class="form-group">';
print '<label for="client_name">'.$langs->trans("ClientName").' (optional)</label>';
print '<input type="text" name="client_name" class="form-control">';
print '</div>';
print '<div class="form-group">';
print '<label for="transfer_code">'.$langs->trans("TransferCode").'</label>';
print '<input type="text" name="transfer_code" class="form-control" required>';
print '</div>';
print '<div class="form-group">';
print '<label for="invoice_number">'.$langs->trans("InvoiceNumber").'</label>';
print '<input type="text" name="invoice_number" class="form-control" required>';
print '</div>';
print '<button type="submit" class="btn btn-primary">'.$langs->trans("Submit").'</button>';
print '</form>';
print '</div>';
print '</div>'; // Fin de la classe container

// Traitement des soumissions
if ($action == 'submit_payment_info') {
    
    $client_name = GETPOST('client_name', 'alpha');
    $transfer_code = GETPOST('transfer_code', 'alpha');
    $invoice_number = GETPOST('invoice_number', 'alpha');

    // Vérifier si la facture existe
    $sql_invoice = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE ref = '$invoice_number' AND fk_statut = 0"; // 0 pour "unpaid"
    $resql_invoice = $db->query($sql_invoice);
    
    if ($resql_invoice && $db->num_rows($resql_invoice) > 0) {
        $invoice = $db->fetch_object($resql_invoice);
        
        // Vérifier si le montant correspond à celui enregistré dans le module Mobile Money
        $sql_payment = "SELECT * FROM ".MAIN_DB_PREFIX."mobilemoney_payments WHERE invoice_number = '$invoice_number' AND transfer_code = '$transfer_code' AND status = 'pending'";
        $resql_payment = $db->query($sql_payment);
        
        if ($resql_payment && $db->num_rows($resql_payment) > 0) {
            $payment = $db->fetch_object($resql_payment);
            if ($payment->amount == $invoice->total_ttc) { // Utiliser total_ttc pour la comparaison
                // Valider le paiement
                $sql_update_payment = "UPDATE ".MAIN_DB_PREFIX."mobilemoney_payments SET status = 'validated' WHERE transfer_code = '$transfer_code'";
                $db->query($sql_update_payment);
                
                // Mettre à jour la facture
                $sql_update_invoice = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 1 WHERE rowid = ".$invoice->rowid; // 1 pour "paid"
                $db->query($sql_update_invoice);
                
                print '<div class="alert alert-success">'.$langs->trans("PaymentValidated").'</div>';
            } else {
                print '<div class="alert alert-danger">'.$langs->trans("PaymentAmountMismatch").'</div>';
            }
        } else {
            // Si aucun paiement en attente n'est trouvé, insérer un nouveau paiement
            $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."mobilemoney_payments (amount, transfer_code, invoice_number, client_name, status, date) VALUES (".$invoice->total_ttc.", '$transfer_code', '$invoice_number', '$client_name', 'pending', NOW())";
            $db->query($sql_insert);
            print '<div class="alert alert-success">'.$langs->trans("PaymentRecorded").'</div>';
        }
    } else {
        print '<div class="alert alert-danger">'.$langs->trans("InvoiceNotFound").'</div>';
    }
}

// End of page
llxFooter();
$db->close();