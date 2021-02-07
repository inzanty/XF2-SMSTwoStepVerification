<?php

namespace INZ\SMSTfa\XF\Pub\Controller;

class Account extends XFCP_Account
{
    /**
     * @return \XF\Mvc\Reply\View
     */
    public function actionPhone()
    {
        /** @var \XF\Entity\User $visitor */
        $visitor = \XF::visitor();
        $phone = $visitor->inztfa_phone_number;

        $viewParams = [
            'phone' => $phone
        ];

        return $this->view('XF:Account\Phone', 'inztfa_sms_account_phone', $viewParams);
    }

    /**
     * @return \XF\Mvc\Reply\Redirect
     * @throws \XF\Db\Exception
     */
    public function actionPhoneSave()
    {
        if ($this->isPost())
        {
            /** @var \XF\Entity\User $visitor */
            $visitor = \XF::visitor();
            $phone = $this->filter('phone', 'str');
            $visitor->fastUpdate('inztfa_phone_number', $phone);
        }

        return $this->redirect('account/phone');
    }
}