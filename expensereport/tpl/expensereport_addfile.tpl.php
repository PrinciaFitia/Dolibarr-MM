<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */

/**
 * @var int $colspan
 */
'
@phan-var-force int $colspan
';

// Add line to upload new file
print '<!-- expensereport_addfile.tpl.php -->'."\n";
print '<tr class="truploadnewfilenow'.(empty($tredited) ? ' oddeven nohover' : ' '.$tredited).'"'.(!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' style="display: none"' : '').'>';

// Num line
if ($action == 'editline') {
	print '<td></td>';
}

// Upload section
print '<td colspan="'.($action == 'editline' ? $colspan - 1 : $colspan).'">';

$modulepart = 'expensereport';
$permission = $user->hasRight('expensereport', 'creer');

// We define var to enable the feature to add prefix of uploaded files
$savingdocmask = '';
if (!getDolGlobalString('MAIN_DISABLE_SUGGEST_REF_AS_PREFIX')) {
	//var_dump($modulepart);
	if (in_array($modulepart, array('facture_fournisseur', 'commande_fournisseur', 'facture', 'commande', 'propal', 'supplier_proposal', 'ficheinter', 'contract', 'expedition', 'project', 'project_task', 'expensereport', 'tax', 'produit', 'product_batch'))) {
		$savingdocmask = dol_sanitizeFileName($object->ref).'-__file__';
	}
}

// Show upload form (document and links)
$formfile->form_attach_new_file(
	$_SERVER["PHP_SELF"].'?id='.$object->id,
	'none',
	0,
	0,
	$permission,
	$conf->browser->layout == 'phone' ? 40 : 60,
	$object,
	'',
	1,
	$savingdocmask,
	0,
	'formuserfile',
	'accept',
	'',
	1
);

print '</td></tr>';
