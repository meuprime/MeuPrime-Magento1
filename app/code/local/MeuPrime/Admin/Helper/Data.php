<?php
class MeuPrime_Admin_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getHost()
    {
        return 'https://api.meuprime.com';
    }

    public function getLooId()
    {
        return Mage::getSingleton('core/session')->getLooid();
    }

    public function setLooData($request)
    {
        $looid = $request->getParam('looid');
        if (!isset($looid) || $looid == '') return;

        $reference = Mage::helper('core/url')->getCurrentUrl(true);
        $media = array(
            "reference"    => $reference,
            "utm_source"   => $request->getParam('utm_source', "none"),
            "utm_campaign" => $request->getParam('utm_campaign', "none"),
            "utm_medium"   => $request->getParam('utm_medium', "site"),
        );

        $jsonData = json_encode($media);
        Mage::log($looid, null, 'meuprime_debug.txt');
        Mage::log($jsonData, null, 'meuprime_debug.txt');
        
        Mage::getSingleton('core/session')->setLoomedia($media);
        Mage::getSingleton('core/session')->setLooid($looid);
    }

    public function getLooMedia()
    {
        $media = Mage::getSingleton('core/session')->getLoomedia();
        return $media;
    }

    public function getOrderItems()
    {
        $items = [];
        $cart = Mage::getModel('checkout/cart');
        $cart_items = $cart->getQuote()->getAllItems();
        foreach ($cart_items as $item) 
        {
            $productId = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($productId);
            $items[] = array(
                "sku"      => $product->getSku(),
                "name"     => $product->getName(),
                "amount"   => $item->getQty(),
                "subtotal" => $item->getPrice(),
                "taxonomy" => "Roupas",
                "weight"   => (float) $product->getWeight(),
            );
        }

        return $items;
    }

    public function getShippingRates($shippingRates)
    {
        $freights = [];
        if (isset($shippingRates) && is_array($shippingRates))
        {
            foreach ($shippingRates as $rate) 
            {
                if ($rate) 
                {
                    $freights[] = array(
                        "label" => $rate->getMethod(),
                        "time"  => (int) $rate->getTimeInTransit(),
                        "value" => $rate->getPrice(),
                    );
                }
            }
        }
        return $freights;
    }

    public function postRequest($url, $postData)
    {
        try 
        {
            $token = Mage::getStoreConfig('shipping/meuprime_admin/token');
            if ($token !== '')
            {
                $jsonData = json_encode($postData);
                Mage::log($jsonData, null, 'meuprime_debug.txt');
                $headers = array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                    'Authorization: Bearer ' . $token,
                );

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                if ($curl_error) throw new Exception($curl_error);
                curl_close($ch);

                Mage::log($response, null, 'meuprime_debug.txt');
                $responseData = json_decode($response, true);
                if (isset($responseData))
                {
                    $newData = $responseData['data'];
                    return $newData;
                }
            }
            return [];
        } catch (Exception $e) {
            Mage::log(json_encode($e->getMessage()), null, 'meuprime_request.txt');
        }
    }
}
?>