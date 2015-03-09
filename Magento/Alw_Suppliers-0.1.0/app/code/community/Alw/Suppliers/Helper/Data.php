<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 * Supplier Helper
 */
class Alw_Suppliers_Helper_Data extends Mage_Core_Helper_Abstract
{
    /* 
	 * Get supplier login Url
	 */
    public function getLoginUrl()
    {
        return Mage::getBaseUrl().'admin?sup=1';
    }
    /* 
	 * Get supplier registration Url
	 */
    public function getAccountUrl()
    {
        return $this->_getUrl('suppliers/index');
    }
	/**
	 * Generate auto password
	 */
	public function generatePassword($length=7)
    {
        return substr(md5(uniqid(rand(), true)), 0, $length);
    }
    /*
  	 * Get supplier collection
	 */
    public function getSupplierCollection($categoryId, $optionsCount)
    {
        $supplierArray = array();
        $getAllSupplier = Mage::getModel('suppliers/suppliername')->getOptionArray();
        foreach ($getAllSupplier as $supId => $value) {
            if ($optionsCount[$supId] > 0) {
                $supplierArray[] = array('id'=>$supId , 'supplierName' => $value );
            }
        }
       return $supplierArray;
    }
    /* 
	 * Get Supplier Name based on his id
	 */
    public function getSupplierDetail($id)
    {
        if ($id > 1) {
            $data = Mage::getModel('admin/user')->load($id);
            $roleData = Mage::getModel('admin/user')->load($id)->getRole()->getData();
            $supplierRole = Mage::getStoreConfig('suppliers/suppliers/role_supplier_user_dropdown');
            if ($roleData['role_id'] == $supplierRole)
                return $data;
        }
    }
    /* 
	 * Delete company Image
	 */
    public function deleteCompanyLogo($logo)
    {
        $path = Mage::getBaseDir('media') . DS;
        if (file_exists($path .$logo)) {
            unlink($path .$logo);
        }
    }
    /*
 	 * Get company logo
	 */
    public function getCompanyLogo($supplier)
    {
        $logo = $supplier->getCompanyLogo();
        if ($logo) {
            $logo = $logo;
        } else {
            $logo = "suppliers/images/small_image.jpg";
        }
        $path = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $logo;
        return $path;
    }
    /* 
     * Check Supplier or not
	 */
    public function checkSupplier($supplierId=1)
    {
        $supplier=0;
        if($supplierId != 1)
        {
            $roleData=Mage::getModel('admin/user')->load($supplierId)->getRole()->getData();
            $supplierRole=Mage::getStoreConfig('suppliers/suppliers/role_supplier_user_dropdown');
            if($roleData['role_id']==$supplierRole)
            {
                $supplier=1;
            }
        }
        return $supplier;
    }
    /*
 	 * Calculate Admin Commission on Supplier Product
	 */
    public function getCommission($supplierId=1,$rowTotal=0,$commissionPer=0)
    {
		Mage::log('commission',null,'commission.log');
        if ($supplierId != 1) {
				$commissionPer=Mage::getStoreConfig('suppliers/suppliers/commissions');				
                $roleData=Mage::getModel('admin/user')->load($supplierId);
                $supplierCommission=$roleData->getCommission();				
                if ($supplierCommission > 0)
                    $commissionPer=$supplierCommission;
        }
        Mage::log('commission'.$commissionPer,null,'commission.log');
        return $commissionPer=($rowTotal*$commissionPer)/100;
    }
	/* 
	 * Check Supplier Wise Order Status
	 */
	public function checkSupplierOrderStatus($orderId,$supplierId)
	{
		$flag = 1;
		$supplierOrderStatus = Mage::getModel('suppliers/supplierorderstatus')->getCollection()
							->addFieldToFilter('supplier_id' ,$supplierId)
							->addFieldToFilter('order_id', $orderId);		
		foreach ($supplierOrderStatus as $supplierStatus) {
			$status[] = $supplierStatus->getStatus();
		}
		if (in_array('hold', $status))
			$flag = 0;
			
		return $flag;	
	}
	/* 
	 * Get Shipping rate of the supplier
	 */
	public function getShippingRate($source,$orderId)
	{
		$shipping='';
		$totalShipping =array();
		$user = Mage::getSingleton('admin/session');
		$supplierId = $user->getUser()->getId();
		$supplierFlag = $this->checkSupplier($supplierId);
		if (!$orderId) {
			$orderId = $source->getOrderId();
		}
		if ($supplierFlag) {
			/* Get all item collection of the supplier */
			$items =  Mage::getResourceModel('sales/order_item_collection')
					->setOrderFilter($orderId)
					->addAttributeToFilter('supplier_id', $supplierId);
		} else {
			$supplierRole = Mage::getStoreConfig('suppliers/suppliers/role_supplier_user_dropdown');
			$allIds = Mage::getModel('admin/role')->getCollection()->addFieldToFilter('parent_id',$supplierRole)->getData();
			foreach ($allIds as $userId)
				$userIds[] = $userId['user_id'];
			/* Get all item collection except supplier items*/
			$items =  Mage::getResourceModel('sales/order_item_collection')
				->setOrderFilter($orderId)
				->addAttributeToFilter('supplier_id', array('nin' => $userIds));
		}
		$creditmemoShipping = $this->getShippingRateOfCreditmemo($source, $orderId);
		/* Get shipping of all items */
		foreach($items as $item) {
			$singleShipping =  $item->getShippingRate()/$item->getQtyOrdered();
			$baseSingleShipping =  $item->getBaseShippingRate()/$item->getQtyOrdered();
			$qty = ($item->getQtyInvoiced() - $item->getQtyRefunded());
			if ($qty == 0) {
				$qty = 1;
				$shippingrate['shipping'] = $singleShipping * $qty;
				$shippingrate['base_shipping'] = $baseSingleShipping * $qty;
			} else{
				$shippingrate['shipping'] += $singleShipping * $qty;
				$shippingrate['base_shipping'] += $baseSingleShipping * $qty;
			}
		}
		$baseTotalShipping = $shippingrate['base_shipping'] - $creditmemoShipping['base_shipping'] ;
		$totalShippingrate = $shippingrate['shipping'] - $creditmemoShipping['shipping'] ;
		if ($totalShipping > 0) {
			$totalShipping['shipping'] = $totalShippingrate;
			$totalShipping['base_shipping'] = $baseTotalShipping;
		} else {
			$totalShipping['base_shipping'] = 0;
			$totalShipping['shipping'] = 0;
		}	
		return $totalShipping;
	}
	/**
	 * Get shipping rate refunded
	 */
	public function getShippingRateOfCreditmemo($source, $orderId)
	{
		$shipping = '';
		if (!$orderId) {
			$orderId = $source->getOrderId();
		}
		$user = Mage::getSingleton('admin/session');
		$supplierId = $user->getUser()->getId();
		$items =  Mage::getModel('sales/order_creditmemo')->getCollection()
			   ->addAttributeToFilter('order_id', $orderId)
			   ->addAttributeToFilter('supplier_id', $supplierId);
		foreach($items as $item) {
			$shipping['shipping'] += $item->getShippingRate();
			$shipping['base_shipping'] += $item->getBaseShippingRate();
		}
		if ($shipping) {
			return $shipping;
		} else {
			$shipping['shipping'] = 0;
			$shipping['base_shipping'] = 0;
			return $shipping;
		}	
	}
	/**
     * Retrieve item status identifier
     *
     * @return int
     */
    public function getStatusId($items)
    {
		$status = array();
		foreach ($items as $item) {
			$backordered = (float)$item->getQtyBackordered();
			if (!$backordered && $item->getHasChildren()) {
				$backordered = (float)$item->_getQtyChildrenBackordered();
			}
	
			$canceled    = (float)$item->getQtyCanceled();
			$invoiced    = (float)$item->getQtyInvoiced();
			$ordered     = (float)$item->getQtyOrdered();
			$refunded    = (float)$item->getQtyRefunded();
			$shipped     = (float)$item->getQtyShipped();
			$protuctType = $item->getProductType();
			if ($protuctType == 'virtual') 
				$actuallyOrdered = 0;
			else 
				$actuallyOrdered = $ordered - $canceled - $refunded;
			if (!$invoiced && !$shipped && !$refunded && !$canceled && !$backordered) {
				$status[] = 'pending';
			} 
			if ($shipped && !$invoiced && ($actuallyOrdered == $shipped)) {
				$status[] = 'processing';
			}
			if ($invoiced < $shipped) {
				$status[] = 'processing';
			}
			if ($invoiced && !$shipped && ($actuallyOrdered == $invoiced)) {
				$status[] = 'processing';
			}
			if (($actuallyOrdered == $shipped) && ($actuallyOrdered == $invoiced)) {
				$status[]  = 'complete';
			}
			if ($refunded && $ordered == $refunded) {
				$status[] = 'closed';
			}
			if ($canceled && $ordered == $canceled) {
				$status[] = 'cancelled';
			}
		}	
		return $status;
    }
}