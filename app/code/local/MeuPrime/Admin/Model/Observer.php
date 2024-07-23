<?php
class MeuPrime_Admin_Model_Observer
{
    public function captureLooid($observer)
    {
        $isEnabled = Mage::getStoreConfig('shipping/meuprime_admin/enabled');
        if (!$isEnabled) return;
        $request = $observer->getEvent()->getControllerAction()->getRequest();
        Mage::helper('meuprime_admin')->setLooData($request);
    }

    public function orderConfirmed(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $order_status = Mage::getStoreConfig('shipping/meuprime_admin/order_status');
        if ($order->getStatus() == $order_status) {
            Mage::getSingleton('core/session')->setLoojobid(null);
            Mage::getSingleton('core/session')->setLooid(null);
            return;
        }
        
        $isEnabled = Mage::getStoreConfig('shipping/meuprime_admin/enabled');
        if (!$isEnabled) {
            Mage::getSingleton('core/session')->setLoojobid(null);
            Mage::getSingleton('core/session')->setLooid(null);
            return;
        }

        $external_id = $order->getIncrementId();
        $loo_job_id = Mage::getSingleton('core/session')->getLoojobid();
        if (!isset($loo_job_id)) {
            Mage::getSingleton('core/session')->setLoojobid(null);
            Mage::getSingleton('core/session')->setLooid(null);
            return;
        }
        
        $postData = array(
            "external_id" => $external_id,
        );

        $url = Mage::helper('meuprime_admin')->getHost() . '/v3/order/' . $loo_job_id . '/confirmed';
        $newData = Mage::helper('meuprime_admin')->postRequest($url, $postData);

        if (isset($newData) && $newData['valid'] == true)
        {
            Mage::getSingleton('core/session')->unsLooJobId();
            Mage::getSingleton('core/session')->unsLooId();
        }

        Mage::getSingleton('core/session')->setLoojobid(null);
        Mage::getSingleton('core/session')->setLooid(null);
    }
}
?>