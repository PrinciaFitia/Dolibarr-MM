<?php
// Charge l'environnement Dolibarr
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Charge les fichiers de traduction nécessaires à la page 
$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));

// Titre de la page
llxHeader("", $langs->trans("Payment Tracking"), '', '', 0, 0, '', '', '', 'mod-mobilemoney page-tracking');

// Inclure le CSS personnalisé dans une balise <style>
echo ' <style>
/* Tableau élégant */
.elegant-table {
    width: 100%;
    border-collapse: collapse; 
    background-color: #fff;
}

.elegant-table th, .elegant-table td {
    padding: 15px; 
    border: 1px solid #ddd; 
    vertical-align: middle;
    font-size: 14px;
    text-align: center;  
}

.elegant-table th {
    background-color: #f8f9fa;
    color: #495057; 
    font-weight: bold;
    border-bottom: 2px solid #007bff;
    text-align: center; 
}

.elegant-table tr:nth-child(even) {
    background-color: #f2f2f2;
}

.elegant-table tr:hover {
    background-color: #e9ecef;
}

/* Bouton retour élégant */
.btn {
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    border-radius: 25px;
    padding: 12px 30px;
    font-size: 18px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn:hover {
    background-color: #0056b3;
}

.btn i {
    margin-right: 10px;
}

/* Centrage du bouton */
.text-center {
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>';


// Affichage du tableau des paiements en attente ou validés
print '<div class="container mt-5">';
print '<h4>'.$langs->trans("Payment List").'</h4>';
print '<table class="table elegant-table mt-3">';
print '<thead>';
print '<tr>';
print '<th>'.$langs->trans("Invoice Number").'</th>';
print '<th>'.$langs->trans("Amount").'</th>';
print '<th>'.$langs->trans("Transfer Code").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
print '</tr>';
print '</thead>';
print '<tbody>';

// Requête pour obtenir les paiements avec l'ID de la facture
$sql = "SELECT p.*, f.rowid AS invoice_id
        FROM ".MAIN_DB_PREFIX."mobilemoney_payments p
        LEFT JOIN ".MAIN_DB_PREFIX."facture f ON p.invoice_number = f.ref
        ORDER BY p.date DESC";
$resql = $db->query($sql);
if ($resql) {
    while ($row = $db->fetch_object($resql)) {
        $payment_status = $langs->trans($row->status); // Utilisation de la traduction du statut
        $invoice_number = $row->invoice_number;
        $invoice_id = $row->invoice_id; // Maintenant, nous avons l'ID de la facture
        $amount = number_format($row->amount, 2, ',', ' ');  // Formatage du montant
        $transfer_code = $row->transfer_code;

        // Générer le lien vers la facture dans le module facture de Dolibarr
        $invoice_link = dol_buildpath('/compta/facture/card.php', 1).'?id='.$invoice_id;

        print '<tr>';
        // Lien vers la page de la facture dans Dolibarr
        print '<td><a href="'.$invoice_link.'" target="_blank">'.$invoice_number.'</a></td>';
        print '<td>'.$amount.'</td>';
        print '<td>'.$transfer_code.'</td>';
        print '<td>'.$payment_status.'</td>';
        print '</tr>';
    }
} else {
    print '<tr><td colspan="4" class="text-center">'.$langs->trans("No payment found").'</td></tr>';
}

print '</tbody>';
print '</table>';
print '</div>'; // End container

// Bouton de retour stylisé avec un design simple mais élégant
print '<div class="text-center mt-4">';
print '<a href="mobilemoneyindex.php" class="btn btn-lg" style="background-color:rgb(37, 43, 49); color: white; border-radius: 5px; padding: 3px 5px; font-size: 12px; text-decoration: none; transition: background-color 0.3s ease;">';
print '<i class="fas fa-arrow-left"></i> '.$langs->trans("Back to Payment Entry");
print '</a>';
print '</div>';

// Footer
llxFooter();
?>
