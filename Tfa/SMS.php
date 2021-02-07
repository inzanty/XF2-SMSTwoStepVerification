<?php

namespace INZ\SMSTfa\Tfa;

use XF\Entity\TfaProvider;
use XF\Tfa\AbstractProvider;

/**
 * Class SMS
 * @package INZ\SMSTfa\Tfa
 */
class SMS extends AbstractProvider
{
    /**
     * @param TfaProvider $provider
     * @return string
     */
    public function renderOptions(TfaProvider $provider)
    {
        $params = [
            'provider' => $provider
        ];

        return \XF::app()->templater()->renderTemplate('admin:two_step_config_inztfa_sms', $params);
    }

    /**
     * @return bool
     */
    public function isUsable()
    {
        return (!empty($this->getProvider()->options['sms_api_key']));
    }

    /**
     * @param \XF\Entity\User $user
     * @param array $config
     * @return array
     */
    public function generateInitialData(\XF\Entity\User $user, array $config = [])
    {
        return [];
    }

    /**
     * @return bool
     */
    public function requiresConfig()
    {
        return true;
    }

    /**
     * @param $context
     * @param \XF\Entity\User $user
     * @param array $config
     * @param \XF\Http\Request $request
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function trigger($context, \XF\Entity\User $user, array &$config, \XF\Http\Request $request)
    {
        $length = 6;

        $random = \XF::generateRandomString(4, true);
        $code = (
                ((ord($random[0]) & 0x7f) << 24 ) |
                ((ord($random[1]) & 0xff) << 16 ) |
                ((ord($random[2]) & 0xff) << 8 ) |
                (ord($random[3]) & 0xff)
            ) % pow(10, $length);
        $code = str_pad($code, $length, '0', STR_PAD_LEFT);

        $config['code'] = $code;
        $config['codeGenerated'] = time();

        $client = \XF::app()->http()->client();
        $phone = $user->inztfa_phone_number;

        $ip = $request->getIp();

        $client->request('POST', 'https://sms.ru/sms/send', [
            'query' => [
                'api_id' => $this->getProvider()->options['sms_api_key'],
                'to' => $phone,
                'msg' => $code,
                'ip' => $ip,
                'json' => 1
            ]
        ]);

        return [];
    }

    /**
     * @param $context
     * @param \XF\Entity\User $user
     * @param array $config
     * @param array $triggerData
     * @return string
     */
    public function render($context, \XF\Entity\User $user, array $config, array $triggerData)
    {
        $phone = $user->inztfa_phone_number;

        $params = [
            'phone' => $phone,
            'config' => $config,
            'context' => $context
        ];

        return \XF::app()->templater()->renderTemplate('public:inztfa_two_step_sms', $params);
    }

    /**
     * @param $context
     * @param \XF\Entity\User $user
     * @param array $config
     * @param \XF\Http\Request $request
     * @return bool
     */
    public function verify($context, \XF\Entity\User $user, array &$config, \XF\Http\Request $request)
    {
        if (empty($config['code']) || empty($config['codeGenerated']))
        {
            return false;
        }

        if (time() - $config['codeGenerated'] > 900)
        {
            return false;
        }

        $code = $request->filter('code', 'str');
        $code = preg_replace('/[^0-9]/', '', $code);

        if (!hash_equals($config['code'], $code))
        {
            return false;
        }

        unset($config['code']);
        unset($config['codeGenerated']);

        return true;
    }

    /**
     * @param \XF\Entity\User $user
     * @param $error
     * @return bool
     */
    public function meetsRequirements(\XF\Entity\User $user, &$error)
    {
        if ($user->user_state != 'valid' || empty($user->inztfa_phone_number))
        {
            $error = \XF::phrase('inztfa_you_must_have_phone_field_filled');
            return false;
        }

        return true;
    }
}