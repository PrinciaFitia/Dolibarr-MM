<?php
// Charge l'environnement Dolibarr
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php'; // Ajout de la classe Facture

// Charge les fichiers de traduction nécessaires à la page 
$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));

// Récupère les paramètres 
$action = GETPOST('action', 'aZ09');
$amount = GETPOST('amount', 'alpha');
$transfer_code = GETPOST('transfer_code', 'alphanohtml');

// Assurer que l'amount est un nombre valide
$amount = str_replace(',', '.', $amount); // Remplace la virgule par un point pour éviter les erreurs de conversion
$amount = floatval($amount);

llxHeader("", $langs->trans("Mobile Money Payment"), '', '', 0, 0, '', '', '', 'mod-mobilemoney page-index');

print load_fiche_titre($langs->trans("Mobile Money Payment"), '', 'payment.png');

// Formulaire de saisie
print '<div class="d-flex justify-content-center align-items-center min-vh-100">';
print '<div class="card shadow-sm p-4" style="max-width: 500px; width: 100%; border-radius: 12px; border: 1px solid #ddd;">';

print '<div class="card-header bg-primary text-white text-center p-3" style="border-radius: 8px 8px 0 0;">';
print '<h4 class="mb-0">'.$langs->trans("Enter Payment Details").'</h4>';
print '</div>';

print '<div class="card-body">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="action" value="validate_payment">';
print '<input type="hidden" name="token" value="'.newToken().'">';

// Champ Montant
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Amount Received").'</label>';
print '<input type="text" name="amount" class="form-control" required>'; 
print '</div>';

// Champ Code de transfert
print '<div class="mb-3">';
print '<label class="form-label">'.$langs->trans("Transfer Code").'</label>';
print '<input type="text" name="transfer_code" class="form-control" required>';
print '</div>';

// Bouton de soumission
print '<button type="submit" class="btn btn-success w-100 p-2 rounded-3">';
print '<i class="fas fa-check"></i> '.$langs->trans("Validate Payment");
print '</button>';

print '</form>';
print '</div>'; 
print '</div>'; 
print '</div>'; 

// Traitement de la validation du paiement
if ($action == 'validate_payment') {
    if ($amount > 0 && !empty($transfer_code)) {
        // Requête pour récupérer le paiement en attente
        $sql = "SELECT invoice_number, amount FROM ".MAIN_DB_PREFIX."mobilemoney_payments 
                WHERE transfer_code = '".$db->escape($transfer_code)."' AND status = 'pending'";
        $resql = $db->query($sql);

        if ($resql && $db->num_rows($resql) == 1) { // Vérifier que le code de transfert est unique
            $obj = $db->fetch_object($resql);
            if (abs(floatval($obj->amount) - $amount) < 0.05) { // Tolérance de 0.05
                // Vérification de la validité de l'ID de la facture dans llx_facture
                $facture = new Facture($db);
                $invoice_number = $obj->invoice_number;
                
                // Rechercher la facture avec la référence
                $sql_facture = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE ref = '".$db->escape($invoice_number)."'";
                $resql_facture = $db->query($sql_facture);

                if ($resql_facture && $db->num_rows($resql_facture) == 1) { // Facture trouvée
                    $facture_data = $db->fetch_object($resql_facture);
                    $facture->fetch($facture_data->rowid); // Charge la facture
                    
                    // Démarrer une transaction
                    $db->begin(); 

                    // Mettre à jour le statut du paiement dans llx_mobilemoney_payments
                    $sql_update_payment = "UPDATE ".MAIN_DB_PREFIX."mobilemoney_payments 
                                           SET status = 'validated' 
                                           WHERE transfer_code = '".$db->escape($transfer_code)."'";
                    if (!$db->query($sql_update_payment)) {
                        print '<div class="alert alert-danger">Error updating payment: '.$db->lasterror().'</div>';
                        error_log("Error updating payment: " . $db->lasterror());
                    } else {
                        // Mise à jour du statut de la facture à "payée"
                        $sql_update_facture = "UPDATE ".MAIN_DB_PREFIX."facture 
                                               SET fk_statut = 2, date_lim_reglement = NOW() 
                                               WHERE ref = '".$db->escape($invoice_number)."'";
                        if ($db->query($sql_update_facture)) {
                            print '<div class="alert alert-success mt-3 text-center">Invoice status successfully updated to paid.</div>';
                            error_log("Invoice status successfully updated for invoice ID: " . $facture->id);
                        } else {
                            print '<div class="alert alert-danger">Error updating invoice status: '.$db->lasterror().'</div>';
                            error_log("Error updating invoice status: " . $db->lasterror());
                        }
                    }

                    $db->commit(); // Valider la transaction
                    print '<div class="alert alert-success mt-3 text-center">'.$langs->trans("Payment successfully validated ").'</div>';
                } else {
                    print '<div class="alert alert-danger">Invoice not found: '.$invoice_number.'</div>';
                    error_log("Invoice not found: " . $invoice_number);
                }
            } else {
                print '<div class="alert alert-danger mt-3 text-center">'.$langs->trans("The entered amount does not match the expected amount.").'</div>';
            }
        } else {
            print '<div class="alert alert-warning mt-3 text-center">'.$langs->trans("No pending payment found for this transfer code.").'</div>';
        }
    } else {
        print '<div class="alert alert-warning mt-3 text-center">'.$langs->trans("Please enter a valid amount and transfer code.").'</div>';
    }
}

llxFooter();
?>