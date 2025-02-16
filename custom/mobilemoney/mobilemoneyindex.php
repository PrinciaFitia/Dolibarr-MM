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
 *    \file       htdocs/mobilemoney/mobilemoneyindex.php
 *    \ingroup    mobilemoney
 *    \brief      Home page of mobilemoney top menu
 */

// Load Dolibarr environment
$res = 0;
$res = @include "/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php";
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));



// Get parameters
$action = GETPOST('action', 'aZ09');

llxHeader("", $langs->trans("Mobile Money Payment"), '', '', 0, 0, '', '', '', 'mod-mobilemoney page-index');

print load_fiche_titre($langs->trans("Mobile Money Payment"), '', 'payment.png');

// Formulaire de saisie des informations par l'employé
print '<div class="container">';
print '<div class="form-section">';
print '<h3>'.$langs->trans("Payment Entry").'</h3>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" class="form-inline">';
print '<input type="hidden" name="action" value="submit_payment_info">';
print '<div class="form-group">';
print '<label for="amount">'.$langs->trans("Amount").'</label>';
print '<input type="text" name="amount" class="form-control" required>';
print '</div>';
print '<div class="form-group">';
print '<label for="transfer_code">'.$langs->trans("TransferCode").'</label>';
print '<input type="text" name="transfer_code" class="form-control" required>';
print '</div>';
print '<div class="form-group">';
print '<label for="client_name">'.$langs->trans("ClientName").' (optional)</label>';
print '<input type="text" name="client_name" class="form-control">';
print '</div>';
print '<button type="submit" class="btn btn-primary">'.$langs->trans("Submit").'</button>';
print '</form>';
print '</div>';
print '</div>'; // Fin de la classe container

// Traitement des soumissions
if ($action == 'submit_payment_info') {
    $client_name = GETPOST('client_name', 'alpha');
    $transfer_code = GETPOST('transfer_code', 'alpha');
    $amount = GETPOST('amount', 'float');

    // Vérifier si une facture existe avec le montant
    $sql_invoice = "SELECT * FROM ".MAIN_DB_PREFIX."llx_facture WHERE total_ttc = $amount AND fk_statut = 1"; // 0 pour "unpaid"

    $resql_invoice = $db->query($sql_invoice);
    
    if ($resql_invoice && $db->num_rows($resql_invoice) > 0) {
        $invoice = $db->fetch_object($resql_invoice);
        
        // Vérifier si le code de transfert a déjà été utilisé
        $sql_payment = "SELECT * FROM ".MAIN_DB_PREFIX."llx_mobilemoney_payments WHERE transfer_code = '$transfer_code' AND status = 'pending'";
        $resql_payment = $db->query($sql_payment);
        
        if ($resql_payment && $db->num_rows($resql_payment) > 0) {
            print '<div class="alert alert-danger">'.$langs->trans("TransferCodeAlreadyUsed").'</div>';
        } else {
            // Enregistrer le paiement
            $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."llx_mobilemoney_payments (amount, transfer_code, client_name, status, date) VALUES ($amount, '$transfer_code', '$client_name', 'pending', NOW())";
            $db->query($sql_insert);
            
            // Mettre à jour la facture
            $sql_update_invoice = "UPDATE ".MAIN_DB_PREFIX."llx_facture SET fk_statut = 1 WHERE rowid = ".$invoice->rowid; // 1 pour "paid"
            $db->query($sql_update_invoice);
            
            print '<div class="alert alert-success">'.$langs->trans("PaymentRecordedAndInvoiceUpdated").'</div>';
        }
    } else {
        print '<div class="alert alert-danger">'.$langs->trans("NoMatchingInvoiceFound").'</div>';
    }
}

// End of page
llxFooter();
$db->close();