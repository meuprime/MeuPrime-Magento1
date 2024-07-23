<?php

class MeuPrime_Admin_Model_Shipping extends Mage_Shipping_Model_Shipping
{

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return $this|MeuPrime_Admin_Model_Shipping
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $storeId = $request->getStoreId();
        if (!$request->getOrig()) {
            $request
                ->setCountryId(Mage::getStoreConfig(self::XML_PATH_STORE_COUNTRY_ID, $request->getStore()))
                ->setRegionId(Mage::getStoreConfig(self::XML_PATH_STORE_REGION_ID, $request->getStore()))
                ->setCity(Mage::getStoreConfig(self::XML_PATH_STORE_CITY, $request->getStore()))
                ->setPostcode(Mage::getStoreConfig(self::XML_PATH_STORE_ZIP, $request->getStore()));
        }

        $limitCarrier = $request->getLimitCarrier();
        if (!$limitCarrier) {
            $carriers = Mage::getStoreConfig('carriers', $storeId);
            foreach ($carriers as $carrierCode => $carrierConfig) {
                $this->collectCarrierRates($carrierCode, $request);
            }
        } else {
            if (!is_array($limitCarrier)) {
                $limitCarrier = array($limitCarrier);
            }
            foreach ($limitCarrier as $carrierCode) {
                $carrierConfig = Mage::getStoreConfig('carriers/' . $carrierCode, $storeId);
                if (!$carrierConfig) {
                    continue;
                }
                $this->collectCarrierRates($carrierCode, $request);
            }
        }

        if (!empty($request->getDestPostcode())) {
            $this->shippingQuote($this->getResult()->getAllRates(), $request->getDestPostcode(), $request->getPackageValue());
        }

        return $this;
    }

    public function shippingQuote($rates, $destinationZipCode, $packageValue)
    {
        $isEnabled = Mage::getStoreConfig('shipping/meuprime_admin/enabled');
        if (!$isEnabled) return;
        $voucher = Mage::helper('meuprime_admin')->getLooId();
        Mage::log('voucher-> ' . $voucher,null,'logfile.log',true);
        if (!isset($voucher) || $voucher == '') return;

        $store = Mage::app()->getStore();
        $shippingOrigin = $store->getConfig('shipping/origin/postcode');
        $shippingRates = $rates;
        $freights = Mage::helper('meuprime_admin')->getShippingRates($shippingRates);
        if (count($freights) <= 0) return;

        $items = Mage::helper('meuprime_admin')->getOrderItems();
        if (count($items) <= 0) return;

        $checkoutSession = Mage::getSingleton('checkout/session');
        $simulate = $checkoutSession->getQuote() && $checkoutSession->getLastRealOrderId();
        $media = Mage::helper('meuprime_admin')->getLooMedia();

        $postData = array(
            "simulate"  => $simulate,
            "media"     => $media,
            "voucher"   => $voucher,
            "warehouse" => array(
                "zipcode" => $shippingOrigin,
            ),
            "shipping"  => array(
                "zipcode" => $destinationZipCode,
            ),
            "items"     => $items,
            "freights"  => $freights,
        );

        $total = $packageValue;
        $cacheId = 'freight' . $voucher . $shippingOrigin . $destinationZipCode . $total;
        if ($cache = Mage::app()->getCache()->load($cacheId))
        {
            $newData = unserialize($cache);
        }
        else
        {
            $url = Mage::helper('meuprime_admin')->getHost() . '/v3/voucher/validate';
            $newData = Mage::helper('meuprime_admin')->postRequest($url, $postData);
        }

        if (isset($newData) && $newData['valid'] == true)
        {
            Mage::app()->getCache()->save(serialize($newData), $cacheId, array("MEU_PRIME"), 10 * 60);
            $newFreights = $newData['freights'];
            if (isset($newFreights) && is_array($newFreights))
            {
                Mage::getSingleton('core/session')->setLoojobid($newData['job_id']);
                $this->getResult()->reset();
                foreach ($rates as $shippingRate)
                {
                    $targetShippingRateLabel = $shippingRate->getMethod();
                    foreach ($newFreights as $freight)
                    {
                        if ($freight['label'] == $targetShippingRateLabel)
                        {
                            $newRatePrice = (float) $freight['value'];
                            $shippingRate->setPrice($newRatePrice);
                            if (empty($newRatePrice)) {
                                if (strpos($shippingRate->getMethodTitle(), "dia(s)") !== false) {
                                    $title = explode("-", $shippingRate->getMethodTitle());
                                    $shippingRate->setMethodTitle('Meu prime - ' . $title[1]);
                                } else {
                                    $title = explode("R$", $shippingRate->getMethodTitle());
                                    $shippingRate->setMethodTitle('Meu prime - ' . $title[1]);
                                }
                            }

                            break;
                        }
                    }

                    $this->getResult()->append($shippingRate);
                }
            }
        }
    }
}
