<?php
namespace ide\protocol\handlers;

use ide\account\api\ServiceResponse;
use ide\commands\AbstractProjectCommand;
use ide\forms\MessageBoxForm;
use ide\forms\SharedProjectDetailForm;
use ide\Ide;
use ide\Logger;
use ide\protocol\AbstractProtocolHandler;
use ide\ui\Notifications;
use php\lib\str;

/**
 * Class OpenProjectProtocolHandler
 * @package ide\protocol\handlers
 */
class OpenProjectProtocolHandler extends AbstractProtocolHandler
{
    /**
     * @param string $query
     * @return bool
     */
    public function isValid($query)
    {
        return str::startsWith($query, 'project:');
    }

    /**
     * @param $query
     * @return bool
     */
    public function handle($query)
    {
        $uid = str::sub($query, str::length('project:'));

        if (str::endsWith($uid, '/')) {
            $uid = str::sub($uid, 0, str::length($uid) - 1);
        }

        if ($uid) {
            Ide::get()->disableOpenLastProject();

            Ide::get()->bind('start', function () use ($uid) {
                uiLater(function () use ($uid) {
                    Ide::service()->projectArchive()->getAsync($uid, function (ServiceResponse $response) use ($uid) {
                        if ($response->isSuccess()) {
                            uiLater(function () use ($response) {
                                Notifications::show('Project found', 'We found a link to a public project, you can open it.', 'INFORMATION');
                                $dialog = new SharedProjectDetailForm($response->result('uid'));
                                $dialog->showAndWait();
                            });
                        } else {
                            Logger::error("Unable to get project, uid = $uid, {$response->toLog()}");
                            Notifications::error('Opening error', 'The link to the project is incorrect or it has already been deleted.');
                        }
                    });
                });
            });
        }
    }
}