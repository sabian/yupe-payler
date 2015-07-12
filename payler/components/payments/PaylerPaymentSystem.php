<?php

Yii::import('application.modules.payler.PaylerModule');
Yii::import('application.modules.payler.components.Payler');

class PaylerPaymentSystem extends PaymentSystem
{
    public function renderCheckoutForm(Payment $payment, Order $order, $return = false)
    {
        $payler = new Payler($payment);
        $sessionId = $payler->getSessionId($order);

        if (!$sessionId) {
            return false;
        }

        $form = CHtml::beginForm($payler->getUrl('Pay'));
        $form .= CHtml::hiddenField('session_id', $sessionId);
        $form .= CHtml::submitButton(Yii::t('PaylerModule.payler', 'Pay'));
        $form .= CHtml::endForm();

        if ($return) {
            return $form;
        } else {
            echo $form;
        }

        return true;
    }

    public function processCheckout(Payment $payment, CHttpRequest $request)
    {
        $payler = new Payler($payment);
        $order = Order::model()->findByUrl($payler->getOrderIdFromHash($request));

        if ($order === null) {
            Yii::log(Yii::t('PaylerModule.payler', 'The order doesn\'t exist.'), CLogger::LEVEL_ERROR);

            return false;
        }

        if ($order->isPaid()) {
            Yii::log(
                Yii::t('PaylerModule.payler', 'The order #{n} is already payed.', $order->getPrimaryKey()),
                CLogger::LEVEL_ERROR
            );

            return false;
        }

        if ($payler->getPaymentStatus($request) === 'Charged' && $order->pay($payment)) {
            Yii::log(
                Yii::t('PaylerModule.payler', 'The order #{n} has been payed successfully.', $order->getPrimaryKey()),
                CLogger::LEVEL_INFO
            );
            Yii::app()->controller->redirect(['/order/order/view', 'url' => $order->url]);

            return true;
        } else {
            Yii::log(Yii::t('PaylerModule.payler', 'An error occurred when you pay the order #{n}.',
                $order->getPrimaryKey()), CLogger::LEVEL_ERROR);

            return false;
        }
    }
}