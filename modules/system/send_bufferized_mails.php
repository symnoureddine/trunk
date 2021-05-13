<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CLogger;
use Ox\Core\CMbException;
use Ox\Mediboard\System\CSMTPBuffer;

CApp::setTimeLimit(60);

CCanDo::checkAdmin();

$buffer = new CSMTPBuffer();

$where = array(
  'attempts' => '< ' . CSMTPBuffer::MAX_ATTEMPTS,
);

$limit = '0, 10';

$buffers = $buffer->loadList($where, 'creation_date ASC', $limit);
$sent    = 0;

foreach ($buffers as $_buffer) {
  try {
    $_buffer->send();
  }
  catch (phpmailerException $e) {
    CApp::log("{$_buffer}: {$e->getMessage()}", null, CLogger::LEVEL_ERROR);
    continue;
  }
  catch (CMbException $e) {
    CApp::log("{$_buffer}: {$e->getMessage()}", null, CLogger::LEVEL_ERROR);
    continue;
  }

  $sent++;
}

if ($sent) {
  CApp::log(CAppUI::tr('CSMTPBuffer-msg-%d sent|pl', $sent));
}