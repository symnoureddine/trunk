<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Sessions;

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;

/**
 * Class CSessionManager
 */
class CSessionManager
{
    /** @var self */
    private static $instance;

    /** @var string */
    private $session_handler;

    /** @var bool */
    private $is_init = false;

    /**
     * CSessionManager constructor.
     *
     * @param string $session_handler
     */
    private function __construct(string $session_handler)
    {
        $this->session_handler = $session_handler;
    }

    public static function get(): self
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        $session_handler = CAppUI::conf("session_handler");
        $instance        = new self($session_handler);
        self::$instance  = $instance;

        return $instance;
    }

    /**
     * @return void
     */
    public function init()
    {

        if ($this->is_init) {
            throw new \RuntimeException('Session is alredy init');
        }

        // Don't ignore user abort as long as session is still locked
        ignore_user_abort(false);

        // Manage the session variable(s)
        $session_name = CAppUI::forgeSessionName();

        session_name($session_name);

        if (get_cfg_var("session.auto_start") > 0) {
            session_write_close();
        }

        CSessionHandler::setHandler($this->session_handler);

        // Start session
        CSessionHandler::start();

        // Ignore aborted HTTP request, so that PHP finishes the current script
        ignore_user_abort(true);

        // Register shutdown function to end the session
        CApp::registerShutdown([CSessionHandler::class, "writeClose"], CApp::SESSION_PRIORITY);


        // Check if the session was made via a temporary token and save its expiration date
        if (isset($_SESSION["token_session"])) {
            CAppUI::$token_expiration = $_SESSION["token_expiration"];
            CAppUI::$token_session    = true;
            CAppUI::$token_id         = $_SESSION["token_id"];
        }

        // Reset session if it expired
        if (CAppUI::isTokenSessionExpired()) {
            CAppUI::$token_expiration = null;
            CAppUI::$token_session    = false;
            CAppUI::$token_id         = null;

            // Free the session data
            CSessionHandler::end(true);

            // Start it back
            CSessionHandler::start();
        }

        // If logout, store real expiration datetime in user_auth object
        if (isset($_GET["logout"]) && isset($_SESSION["AppUI"])) {
            // Use $_SESSION because CAppUI::$instance is not set yet (see below)
            $last_auth = $_SESSION["AppUI"]->_ref_last_auth;

            // Remove the session cookie upon user logout
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 1000, '/');
            }

            if ($last_auth && $last_auth->_id) {
                $dtnow                          = CMbDT::dateTime();
                $last_auth->expiration_datetime = $dtnow;
                $last_auth->last_session_update = $dtnow;
                $last_auth->nb_update++;
                $last_auth->store();
            }
        }

        // Check if session has previously been initialised
        if (empty($_SESSION["AppUI"]) || isset($_GET["logout"])) {
            $_SESSION["AppUI"] = CAppUI::initInstance();
        }

        CAppUI::$instance               =& $_SESSION["AppUI"];
        CAppUI::$instance->session_name = $session_name;
        if (!isset($_SESSION["locked"])) {
            $_SESSION["locked"] = false;
        }

        CAppUI::checkSessionUpdate();

        // Tell to not revive the session on hit
        CAppUI::$session_no_revive = (bool)(($_GET['session_no_revive']) ?? false);

        $this->is_init = true;
    }

    /**
     * @return void
     */
    public function terminate(): void
    {
        if (CApp::isSessionRestricted()) {
            CSessionHandler::end(true);
        } else {
            // Explicit close of the session before object destruction
            CSessionHandler::writeClose();
        }
    }

    public function getSessionHandler(): ?string
    {
        return $this->session_handler;
    }
}
