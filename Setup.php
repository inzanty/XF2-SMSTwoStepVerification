<?php

namespace INZ\SMSTfa;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * @throws \XF\PrintableException
     */
    public function installStep1()
    {
        $this->registerTfaProvider('inztfa_sms', 'INZ\SMSTfa:SMS', 500);
    }

    public function installStep2()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_user', function (Alter $table) {
            $table->addColumn('inztfa_phone_number', 'text');
        });
    }

    /**
     * @throws \XF\PrintableException
     */
    public function uninstallStep1()
    {
        $this->destroyTfaProvider('inztfa_sms');
    }

    public function uninstallStep2()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_user', function (Alter $table) {
            $table->dropColumns('inztfa_phone_number');
        });
    }

    /**
     * @param $id
     * @param $handler
     * @param $priority
     * @param bool $is_active
     * @param null $provider
     * @return bool
     * @throws \XF\PrintableException
     */
    private function registerTfaProvider($id, $handler, $priority, $is_active = true, &$provider = null)
    {
        $em = $this->app()->em();
        $provider = $em->find('XF:TfaProvider', $id);

        if ($provider)
        {
            return false;
        }

        $provider = $em->create('XF:TfaProvider');
        $provider->bulkSet([
            'provider_id' => $id,
            'provider_class' => $handler,
            'priority' => $priority,
            'active' => intval($is_active)
        ]);

        return $provider->save();
    }

    /**
     * @param $id
     * @return bool
     * @throws \XF\PrintableException
     */
    protected function destroyTfaProvider($id)
    {
        $provider = $this->app()->em()->find('XF:TfaProvider', $id);

        if (!$provider)
        {
            return true;
        }

        return $provider->delete();
    }
}