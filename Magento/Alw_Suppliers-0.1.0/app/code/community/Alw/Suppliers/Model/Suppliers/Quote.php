<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 * Split shipping according to the supplier
 */
class Alw_Suppliers_Model_Suppliers_Quote
{
    const SHIP_CARRIER_ALL = "ALL";
    const MULTIPLE_SHIP_METHOD = "mso_DEFAULT";
    protected $_quote;
    /**
     * Set quote object for get multiple ship data.
     */
    public function setQuote(Mage_Sales_Model_Quote $quote){
        $this->_quote = $quote;
        return $this;
    }
    /**
     * The checkout will be a multiple ship origin checkout if:
     * - There are at least two products with two different ship origin (zip, region, country code, ship carrier) OR
     * - There are one default (no assigned origin so can choose multiple ship carrier) product and at least one assigned ship origin.  
     */
    public function isMultipleShipOriginQuote()
    {
        $numberOfAssignedShipOrigin = $this->getNumberOfAssignedShipOrigin();
        if($numberOfAssignedShipOrigin > 1){
            return true;
        } else{
            return false;
        }
    }
    /**
     * The checkout will be a single ship origin checkout if:
     * - There is all assigned ship origin product and them all share a common ship origin (zip, region code, country code, ship carrier). 
     */    
    public function isSingleShipOriginQuote(){
        $numberOfAssignedShipOrigin = $this->getNumberOfAssignedShipOrigin();
        if($numberOfAssignedShipOrigin == 1){
            return true;
        } else{
            return false;
        }
    }
    /**
     * Get the number of assigned ship origin of quote item.
     *
     * @return integer
     */
    protected function getNumberOfAssignedShipOrigin(){
        $shipCombinations = $this->buildShipCombinations();
        $numberOfShipCombinations = count($shipCombinations);
        $shipCombinationsData = array_values($shipCombinations);
        if($numberOfShipCombinations == 1){
            if($shipCombinationsData[0]->shipCarrier == self::SHIP_CARRIER_ALL){
                return 0; 
            } else{
                return 1;
            }
        } else if($numberOfShipCombinations > 1){
            return $numberOfShipCombinations;
        }
        
        return 0;
    }
    protected function getMultipleShipOriginOfProduct($productId, $itemId)
    {
        $product = Mage::getModel('catalog/product')
                 ->setStoreId(Mage::app()->getStore()->getId())
                 ->load($productId);
        /* @var $product Mage_Catalog_Model_Product */
        if(!isset($product)) return NULL;
        $supplierId = $product->getData('created_by');
        $user = Mage::getModel('admin/user')->load($supplierId);
        $shippingMethod = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod();
        $shippingMethod = explode("_",$shippingMethod);
        $productShipOrigin = new stdClass();
        $productShipOrigin->zip = trim($user->getZipCode());
        $productShipOrigin->regionCode = trim($user->getState());
        $productShipOrigin->countryCode = strtoupper(trim($user->getCountry()));
        $productShipOrigin->shipCarrier = $shippingMethod[0] ;
        $productShipOrigin->productId = $itemId; 
        $thisProductHasShipOrigin = false;
        if((!empty($productShipOrigin->zip)) && (!empty($productShipOrigin->regionCode)) && (!empty($productShipOrigin->countryCode)) && (!empty($productShipOrigin->shipCarrier))){                
            $thisProductHasShipOrigin = true;
        }
        if(!$thisProductHasShipOrigin){  
            $productShipOrigin = new stdClass();              
            $request = Mage::getModel('shipping/rate_request');
            $regionCode = Mage::getStoreConfig('shipping/origin/region_id', $request->getStore());
            if (is_numeric($regionCode)) {
                $regionCode = Mage::getModel('directory/region')->load($regionCode)->getCode();
            }
            $productShipOrigin->zip = Mage::getStoreConfig('shipping/origin/postcode', $request->getStore());
            $productShipOrigin->regionCode = $regionCode;
            $productShipOrigin->countryCode = Mage::getStoreConfig('shipping/origin/country_id', $request->getStore());
            $productShipOrigin->shipCarrier = $shippingMethod[0] ;
            $productShipOrigin->productId = $itemId; 
        }        
        $productShipOrigin->shipCombinationKey = $this->buildCombinationKey($productShipOrigin->zip,$productShipOrigin->regionCode,$productShipOrigin->countryCode,$productShipOrigin->shipCarrier,$productShipOrigin->productId);
        return $productShipOrigin;
    }
    /**
     * Get quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote(){
        /**
         *  if the calling object actively set the quote to use, then use it.
         *  it's obligated in the frontend and in cart page to avoid the issue of 
         *     out of memory when user see the cart and at the same time admin user change product data.
         */
        if(isset($this->_quote)){
            return $this->_quote;
        }
        $request = Mage::app()->getRequest();
        $moduleName = strtolower(trim($request->getModuleName()));
        $controllerName = strtolower(trim($request->getControllerName()));
        if ($moduleName == "checkout"){
            //2. get quote data of in session (frontend)
            $checkout = Mage::getSingleton('checkout/session');
            /* @var $checkout Mage_Checkout_Model_Session */
            return $checkout->getQuote();
        } else if ($moduleName == "admin" && $controllerName == "sales_order_create"){
            //3. in admin, at create order.
            $session = Mage::getSingleton('adminhtml/session_quote');
            /* @var $session Mage_Adminhtml_Model_Session_Quote */
            return $session->getQuote();
        }
        return false;
    }
    /**
     * Build ship combination array
     *
     * @return array
     */
    public function buildShipCombinations()
    {
        $shipCombinations = array();
        
        $quote = $this->getQuote();
        Mage::log('quoteModel',null,'quote.log');
        if(!$quote){
                Mage::log('not foundquote',null,'quote.log');
            return $shipCombinations;
        }
        $items = $quote->getItemsCollection();
        Mage::log('items',null,'quote.log');
        foreach ($items as $item) {
            $productId = $item->getData('product_id');
            Mage::log('quote'.$productId,null,'quote.log');
            $result = $this->getShippingDataOfQuoteItem($item);
            //skip virtual product and child product of configurable item.
            if(!$result){
                continue;
            }
            //if get an array of ship combination of bundle children products
            if (is_array($result)) {
               foreach($result as $shipCombinationKey => $resultItems){
                    foreach($resultItems as $resultItem){
                        if(!array_key_exists($shipCombinationKey,$shipCombinations)){
                            $shipCombinationData = $resultItem;
                            $shipCombinationData->items[] = $resultItem->quoteItem;
                             
                            $shipCombinationData->subTotal = $resultItem->rowTotal;
                            
                            unset($shipCombinationData->quoteItem);
                            
                            $shipCombinations[$shipCombinationKey] = $shipCombinationData;
                            
                        } else{
                            $shipCombinations[$shipCombinationKey]->weight += $resultItem->weight;
                            $shipCombinations[$shipCombinationKey]->itemQty += $resultItem->itemQty;  
                            $shipCombinations[$shipCombinationKey]->items[] = $resultItem->quoteItem;
                            $shipCombinations[$shipCombinationKey]->subTotal += $resultItem->rowTotal;
                            $shipCombinations[$shipCombinationKey]->freeMethodWeight += $resultItem->freeMethodWeight;
                        }                    
                    }
                }
            } 
            /**
             * - configurable product.
             * - bundle product with common shared ship combination between its children products.
             */
            else{
                if (!array_key_exists($result->shipCombinationKey,$shipCombinations)) {
                    $shipCombinationData = $result;
                    $shipCombinationData->items[] = $result->quoteItem;
                    $shipCombinationData->subTotal = $item->getRowTotal();
                    unset($shipCombinationData->quoteItem);
                    $shipCombinations[$result->shipCombinationKey] = $shipCombinationData;
                } else {
                    $shipCombinations[$result->shipCombinationKey]->weight += $result->weight;
                    $shipCombinations[$result->shipCombinationKey]->itemQty += $result->itemQty;  
                    $shipCombinations[$result->shipCombinationKey]->items[] = $result->quoteItem;
                    $shipCombinations[$result->shipCombinationKey]->subTotal += $item->getRowTotal();
                    $shipCombinations[$result->shipCombinationKey]->freeMethodWeight += $result->freeMethodWeight;
                }
            }                
        }
        return $shipCombinations;        
    }
    /**
     * check if item has shipping data, if not return false.
     * @return object|false
     */
    protected function getShippingDataOfQuoteItem(Mage_Sales_Model_Quote_Item $item){
        $addressQty = 0;
        $addressWeight = 0;
        $freeMethodWeight = 0;
        $rowWeight = 0;
        Mage::log('getShippingDataOfQuoteItem',null,'quote.log');
        $address = $item->getQuote()->getShippingAddress();
        $freeAddress = $address->getFreeShipping();
        //skip virtual product
        if ($item->getProduct()->getTypeInstance()->isVirtual()) {
            return false;
        }
        /**
         * Children weight we calculate for parent
         */
        //@note: skip child product of configuable/bundle product.
        if ($item->getParentItem()) {
            return false;
        }
        /**
         * @note: if this item has chidren products, then it's a configurable product or bundle product.
         *   With product type belong to class:
         *    - Mage_Bundle_Model_Product_Type.
         *    - Mage_Catalog_Model_Product_Type_Configurable
         * @note: 
         *   - bundle product item: $item->isShipSeparately() = 1 OR 0
         *   -- 0: Ship Bundle Items: Together
         *   -- 1: Ship Bundle Items: Separately
         *   - configurable product item: $item->isShipSeparately() = null.
         * 
         * @final note:
         *   - bundle product will pass the ($item->getHasChildren() && $item->isShipSeparately())
         *   -- configurable product will not pass that if condition.
         *   - isShipSeparately(): bundle product can be ship together or separately, 
         *     we skip that option and always base on ship origin of children product to make decision.  
         */
        if ($item->getHasChildren() && (get_class($item->getProduct()->getTypeInstance()) == "Mage_Bundle_Model_Product_Type")){
            
            //extract data from of bundle product's children item.
            $allChildrenProductsAreVirtual = true;
            $allChildrenProductsHaveCommonShipCombination = true;
            
            $childrensShipCombinations = array();
            foreach ($item->getChildren() as $child) {
                if ($child->getProduct()->getTypeInstance()->isVirtual()) {
                    continue;
                }
                $allChildrenProductsAreVirtual = false;
                $itemWeight = $child->getWeight();
                $itemQty    = $item->getQty()*$child->getQty();
                $rowWeight  = $itemWeight*$itemQty;
                Mage::log('items'.$itemWeight."-". $itemQty."-".$rowWeight,null,'quote.log');
                $addressWeight += $rowWeight;
                $addressQty += $item->getQty()*$child->getQty();
                $childShipOrigin = $this->getMultipleShipOriginOfProduct($child->getProduct()->getId(), $child->getId());
                $childShipOrigin->itemQty = $itemQty;
                $childShipOrigin->weight = $rowWeight;
                $childShipOrigin->quoteItem = $child;
                $childShipOrigin->rowTotal = $child->getRowTotal();
                $childrensShipCombinations[$childShipOrigin->shipCombinationKey][] =  $childShipOrigin;
                if ($freeAddress || $child->getFreeShipping()===true) {
                    $rowWeight = 0;
                } elseif (is_numeric($child->getFreeShipping())) {
                    $freeQty = $child->getFreeShipping();
                    if ($itemQty>$freeQty) {
                        $rowWeight = $itemWeight*($itemQty-$freeQty);
                    } else {
                        $rowWeight = 0;
                    }
                }
                $childShipOrigin->freeMethodWeight = $rowWeight;
                $freeMethodWeight += $rowWeight;
            }
            if(count($childrensShipCombinations) > 1){
                $allChildrenProductsHaveCommonShipCombination = false;
            }
            $bundleProductShipOrigin = $this->getMultipleShipOriginOfProduct($item->getProduct()->getId(),$item->getId());
            $bundleItemHasShipOrigin = false;
            if($bundleProductShipOrigin->shipCarrier != self::SHIP_CARRIER_ALL){
                $bundleItemHasShipOrigin = true;
            }
            /* bundle product with all virtual children product. */
            if($allChildrenProductsAreVirtual){
                return false;
            } 
            else if(($item->isShipSeparately() == 0 && $bundleItemHasShipOrigin) || $allChildrenProductsHaveCommonShipCombination){
                /*
                 * if 
                 * - bundle item has option: "Ship Bundle Items: Together"
                 * - admin explicit assign a ship origin and ship carrier for bundle item
                 * then use ship origin of bundle item itself
                 */                
                if (($item->isShipSeparately() == 0 && $bundleItemHasShipOrigin)) {
                    $shipData = $bundleProductShipOrigin;
                }
                /**
                 * @note
                 *  if all children item share a common ship origin, 
                 *  then use that common ship origin.  
                 */
                else if($allChildrenProductsHaveCommonShipCombination){
                    /* use ship combination data from common one. */
                    $childrensShipCombinations = array_values($childrensShipCombinations);
                    $childrensShipCombinations = $childrensShipCombinations[0][0];
                    $shipData = $childrensShipCombinations;
                }
                $shipData->quoteItem = $item;
                $shipData->itemQty = $item->getQty();//$addressQty;                
                /**
                 *  if (bundle) parent product item's weight type :
                 *  = 0 : Dynamic, so the final weight will be the summary of child weight (except virtual one).
                 *  = 1 : Fixed, so the final weight always fixed and equal the parent bundle one. No need to pay attention at child weight.
                 * 
                 * @note for multiple ship extension : this if case is for:
                 * - bundle product with fixed weight type and all children ship with same ship origin and ship carrier.
                 * - fixed weight type: get weight from the bundle item.
                 */
                if ($item->getProduct()->getWeightType()) {
                    $itemWeight = $item->getWeight();
                    $rowWeight  = $itemWeight*$item->getQty();
                    $shipData->rowWeight = $rowWeight;
                    $shipData->weight = $rowWeight;
                    if ($freeAddress || $item->getFreeShipping()===true) {
                        $rowWeight = 0;
                    } elseif (is_numeric($item->getFreeShipping())) {
                        $freeQty = $item->getFreeShipping();
                        if ($item->getQty()>$freeQty) {
                            $rowWeight = $itemWeight*($item->getQty()-$freeQty);
                        } else {
                            $rowWeight = 0;
                        }
                    }
                    $shipData->freeMethodWeight = $rowWeight;                    
                } 
                /**
                 * @note: this else case is for:
                 * - bundle product with dynamic weight type and all children ship with same ship origin and ship carrier.
                 * - dynamic weight type: get total weight from children item.
                 */
                else {
                    $shipData->rowWeight = $addressWeight;
                    $shipData->weight = $addressWeight;        
                    $shipData->freeMethodWeight = $freeMethodWeight;            
                }
                return $shipData;                
            }
            /**
             * @note for multiple ship extension: this else if case is for:
             *  - bundle product with children product ship from different ship combination (zip + ship carrier).
             *  - don't pay attention on bundle product's weight type
             * 
             */ 
            else if (!$allChildrenProductsHaveCommonShipCombination) {
                return $childrensShipCombinations;
            }
        }
        /**
         *   - standalone simple product which: 
         *     - product type : Mage_Catalog_Model_Product_Type_Simple.
         *     - this product doesn't associate with any other configurable/bundle product. 
         *   - for configurable product which:
         *     - product type : Mage_Catalog_Model_Product_Type_Configurable
         */
        else {
            $shipData = new stdClass();
            $shipData->itemQty = $item->getQty();
            $shipData->quoteItem = $item;
            /* this is a simple independent product, so hasn't got children products. */
            if (!$item->getHasChildren()) {
                Mage::log('itemshaschildren',null,'quote.log');
                $itemWeight = $item->getWeight();
                $rowWeight  = $itemWeight*$item->getQty();
                $shipData->weight = $rowWeight;
                $shipData->rowWeight = $rowWeight;
                Mage::log('items'.$itemWeight."-".$rowWeight."-".$shipData->weight,null,'quote.log');        
                $shipOrigin = $this->getMultipleShipOriginOfProduct($item->getProduct()->getId(), $item->getId());
                $shipData->zip = $shipOrigin->zip;
                $shipData->regionCode = $shipOrigin->regionCode;
                $shipData->countryCode = $shipOrigin->countryCode;
                $shipData->shipCarrier = $shipOrigin->shipCarrier;
                $shipData->shipCombinationKey = $shipOrigin->shipCombinationKey;
            } else {
                /* this is a configurable product, with simple associated product as child one. */
                Mage::log('itemsnochildren',null,'quote.log');
                $itemWeight = $item->getWeight();
                $rowWeight  = $itemWeight*$item->getQty();
                $shipData->weight = $rowWeight;
                $shipData->rowWeight = $rowWeight;
                Mage::log('items'.$itemWeight."-".$rowWeight."-".$shipData->weight,null,'quote.log');        
                $child = $item->getChildren();
                $child = array_values($child);
                $child = $child[0];
                $shipOrigin = $this->getMultipleShipOriginOfProduct($child->getProduct()->getId(),  $child->getId());
                if($shipOrigin->shipCarrier == self::SHIP_CARRIER_ALL){
                    /* get the ship origin of configurable product itself. */
                    $shipOrigin = $this->getMultipleShipOriginOfProduct($item->getProduct()->getId(),  $item->getId());
                }
                $shipData->zip = $shipOrigin->zip;
                $shipData->regionCode = $shipOrigin->regionCode;
                $shipData->countryCode = $shipOrigin->countryCode;
                $shipData->shipCarrier = $shipOrigin->shipCarrier;    
                $shipData->shipCombinationKey = $shipOrigin->shipCombinationKey;                
            }
            if ($freeAddress || $item->getFreeShipping()===true) {
                $rowWeight = 0;
            } elseif (is_numeric($item->getFreeShipping())) {
                $freeQty = $item->getFreeShipping();
                if ($item->getQty()>$freeQty) {
                    $rowWeight = $itemWeight*($item->getQty()-$freeQty);
                } else { 
                    $rowWeight = 0;
                }
            } 
            $shipData->freeMethodWeight = $rowWeight;
            return $shipData;
        }
    }
    /**
     * Build combination key from origin zip, origin country code and assigned shipping carrier
     *
     * @return string
     */
    public function buildCombinationKey($zip,$regionCode,$countryCode,$shipCarrier, $productId){
        $combinationKey = $zip . "-" . $regionCode . "-" . $countryCode . "-" . $shipCarrier."-".$productId;
        return $combinationKey;
    }
    /**
     * Return multiple ship combination key array for validation in javascript of 
     * checkout/onepage/shipping_method/available.phtml
     */
    public function getMultipleShipCombinationKeys(){
        $multipleShipCombinationKeys = $this->buildShipCombinations();
        $multipleShipCombinationKeys = array_keys($multipleShipCombinationKeys);
        $multipleShipCombinationKeys = "\"" . implode("\",\"",$multipleShipCombinationKeys) . "\"";
        return $multipleShipCombinationKeys;
    }    
}