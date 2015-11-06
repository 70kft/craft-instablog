<?php
namespace Craft;

class InstaBlog_UninstallController extends BaseController
{
    public function actionRun()
    {
        $this->requirePostRequest();
        craft()->userSession->requireAdmin();

        if (craft()->instaBlog_uninstall->run())
        {
            craft()->userSession->setNotice(Craft::t('InstaBlog Uninstall Successfully.'));
            $this->redirectToPostedUrl();
        }
        else
        {
            // Prepare a flash error message for the user.
            craft()->userSession->setError(Craft::t('Couldn’t Uninstall InstaBlog. Check Craft Log.'));
            $this->redirectToPostedUrl();

        }
    }
}