<?php

/*----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2008 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi-project.org/
   ----------------------------------------------------------------------
   LICENSE

   This file is part of GLPI.

   GLPI is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with GLPI; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   ----------------------------------------------------------------------*/
/*----------------------------------------------------------------------
    Original Author of file: Benjamin Fontan
    Purpose of file:
    ----------------------------------------------------------------------*/

function getQuantity($FK_order, $FK_reference) {
	global $CFG_GLPI, $DB;
	$query = "	SELECT count(*) AS quantity FROM glpi_plugin_order_detail
								WHERE FK_order=$FK_order
								AND FK_reference=$FK_reference";
	$result = $DB->query($query);
	return ($DB->result($result, 0, 'quantity'));
}

function getDelivredQuantity($FK_order, $FK_reference) {
	global $CFG_GLPI, $DB;
	$query = "	SELECT count(*) AS delivredquantity FROM glpi_plugin_order_detail
								WHERE FK_order=$FK_order
								AND FK_reference=$FK_reference
								AND status='1'";
	$result = $DB->query($query);
	return ($DB->result($result, 0, 'delivredquantity'));
}

function getPrices($FK_order) {
	global $CFG_GLPI, $DB;
	$query = "SELECT SUM(price_ati) as priceTTC, SUM(price_discounted) as priceHT FROM `glpi_plugin_order_detail` WHERE FK_order=$FK_order";
	$result = $DB->query($query);
	return $DB->fetch_array($result);
}

function getPriceTaxIncluded($priceHT, $taxes) {
	if (!$priceHT)
		return 0;
	else
		return $priceHT + (($priceHT * $taxes) / 100);
}

function addDetails($referenceID, $orderID, $quantity, $price, $discounted_price, $taxes) {
	global $LANG;
	if (referenceExistsInOrder($orderID,$referenceID))
		addMessageAfterRedirect($LANG['plugin_order']['detail'][28],false,ERROR);
	
	if ($quantity > 0) {
		$detail = new plugin_order_detail;
		for ($i = 0; $i < $quantity; $i++) {
			$input["FK_order"] = $orderID;
			$input["FK_reference"] = $referenceID;
			$input["price_taxfree"] = $price;
			$input["price_discounted"] = $price-($price*($discounted_price/100));
			$input["status"] = ORDER_STATUS_DRAFT;
			$input["price_ati"] = getPriceTaxIncluded($input["price_discounted"], $taxes);
			$detail->add($input);
		}
	}
}

function referenceExistsInOrder($orderID,$referenceID)
{
	global $DB;
	$query = "SELECT ID FROM `glpi_plugin_order_detail` WHERE FK_order=$orderID AND FK_reference=$referenceID";
	$result = $DB->query($query);
	if ($DB->numrows($result))
		return true;
	else
		return false;	
}
function deleteDetails($referenceID, $orderID) {
	global $DB;
	
	$query = " DELETE FROM `glpi_plugin_order_detail`
				WHERE FK_order=$orderID 
				AND FK_reference=$referenceID";
	$DB->query($query);

	$query = " DELETE FROM `glpi_plugin_order_device`
				WHERE FK_order=$orderID ";
	$DB->query($query);

}
?>