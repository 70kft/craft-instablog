<?php
namespace Craft;

class InstaBlog_InstallController extends BaseController
{
    public function actionRun()
    {
        $this->requirePostRequest();
        craft()->userSession->requireAdmin();

        if (craft()->instaBlog_install->run())
        {
            craft()->userSession->setNotice(Craft::t('InstaBlog Installed Successfully.'));
            $this->redirectToPostedUrl();
        }
        else
        {
            // Prepare a flash error message for the user.
            craft()->userSession->setError(Craft::t('Couldn’t Install InstaBlog. Check Craft Log.'));
            $this->redirectToPostedUrl();

        }
    }
}