--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--
CREATE TABLE `llx_mobilemoney_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,             -- Identifiant unique pour chaque paiement
    `amount` DECIMAL(15, 2) NOT NULL,                 -- Montant payé
    `transfer_code` VARCHAR(255) NOT NULL,            -- Code unique du transfert
    `invoice_number` VARCHAR(50) NOT NULL,            -- Référence de la facture liée au paiement
    `client_name` VARCHAR(255) NOT NULL,              -- Nom du client effectuant le paiement
    `status` ENUM('pending', 'validated') NOT NULL DEFAULT 'pending', -- Statut du paiement (en attente ou validé)
    `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Date de création du paiement
    PRIMARY KEY (`id`),                               -- Clé primaire (id unique)
    UNIQUE KEY `transfer_code` (`transfer_code`),     -- Empêcher les doublons de code de transfert
    KEY `invoice_number` (`invoice_number`)           -- Index sur la référence de la facture
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


// à ajouter dans PhpMyadmin .
