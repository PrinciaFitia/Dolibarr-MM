<?php
require '/Applications/MAMP/htdocs/dolibarr2.0/main.inc.php'; 

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php'; 
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array('admin', 'bills', 'mobilemoney'));

llxHeader("", $langs->trans("Payment Transactions"), '', '', 0, 0, '', '', '', 'mod-mobilemoney page-transactions');

print load_fiche_titre($langs->trans("Payment Transactions"), '', 'payment.png');

// Récupérer les filtres de recherche
$search_invoice = GETPOST('search_invoice', 'alpha');
$search_status = GETPOST('search_status', 'alpha');
$search_date = GETPOST('search_date', 'alpha');

// Construire la requête SQL avec filtres
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mobilemoney_transactions WHERE 1=1";

if (!empty($search_invoice)) {
    $sql .= " AND invoice_number LIKE '%".$db->escape($search_invoice)."%'";
}

if (!empty($search_status)) {
    $sql .= " AND status = '".$db->escape($search_status)."'";
}

if (!empty($search_date)) {
    $sql .= " AND DATE(date) = '".$db->escape($search_date)."'";
}

$sql .= " ORDER BY date DESC";

$resql = $db->query($sql);

print '<div class="container mt-3">';
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<div class="row">';
print '<div class="col-md-3"><input type="text" name="search_invoice" class="form-control" placeholder="'.$langs->trans("Search Invoice Number").'" value="'.$search_invoice.'"></div>';
print '<div class="col-md-3">';
print '<select name="search_status" class="form-control">';
print '<option value="">'.$langs->trans("All Status").'</option>';
$statuses = array('pending', 'validated', 'partial', 'overpaid');
foreach ($statuses as $status) {
    print '<option value="'.$status.'"'.($search_status == $status ? ' selected' : '').'>'.$langs->trans(ucfirst($status)).'</option>';
}
print '</select>';
print '</div>';
print '<div class="col-md-3"><input type="date" name="search_date" class="form-control" value="'.$search_date.'"></div>';
print '<div class="col-md-3"><button type="submit" class="btn btn-primary">'.$langs->trans("Filter").'</button></div>';
print '</div>';
print '</form>';
print '</div>';

print '<table class="table table-striped mt-3">';
print '<tr><th>Invoice</th><th>Amount</th><th>Transfer Code</th><th>Status</th><th>Date</th></tr>';

while ($obj = $db->fetch_object($resql)) {
    print '<tr>';
    print '<td>'.$obj->invoice_number.'</td>';
    print '<td>'.$obj->amount.'</td>';
    print '<td>'.$obj->transfer_code.'</td>';
    print '<td>'.$langs->trans(ucfirst($obj->status)).'</td>';
    print '<td>'.$obj->date.'</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
?>
