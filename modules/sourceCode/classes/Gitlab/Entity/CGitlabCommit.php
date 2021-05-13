<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode\Gitlab\Entity;

use DateTime;
use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CMbObjectSpec;
use Ox\Core\CStoredObject;
use Ox\Erp\SourceCode\Gitlab\Manager\CGitlabManager;
use Ox\Erp\Tasking\CTaskingTicket;
use Ox\Erp\Tasking\CTaskingTicketCommit;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Class CGitlabCommit
 *
 * @package Ox\Erp\SourceCode\Gitlab\Entity
 */
class CGitlabCommit extends CMbObject
{

    public const TYPES = ['fnc', 'bug', 'ref', 'erg', 'test', 'scm', 'trad'];
    public const API_PAGE_LIMIT = 100;

    /** @var int Primary key */
    public $ox_gitlab_commit_id;

    /** @var string */
    public $id;

    /** @var string */
    public $short_id;

    /** @var string */
    public $title;

    /** @var string */
    public $message;

    /** @var string */
    public $author_email;

    /** @var DateTime */
    public $authored_date;

    /** @var string */
    public $web_url;

    /** @var string */
    public $type;

    /** @var int */
    public $ox_user_id;

    /** @var int */
    public $ox_gitlab_branch_id;

    /** @var array */
    public $_types_list_multi;

    /** @var CMediusers */
    public $_ref_user;

    /** @var CGitlabBranch */
    public $_ref_branch = [];

    /** @var CGitlabProject */
    public $_ref_project = [];

    /** @var CTaskingTicket[] */
    public $_ref_tasks = [];

    /**
     * @inheritdoc
     */
    public function getSpec(): CMbObjectSpec
    {
        $spec           = parent::getSpec();
        $spec->table    = "gitlab_commit";
        $spec->key      = "ox_gitlab_commit_id";
        $spec->loggable = false;

        $spec->uniques['commit'] = ['id'];

        return $spec;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): array
    {
        $props                        = parent::getProps();
        $props['id']                  = 'str notNull minLength|7 maxLength|40';
        $props['short_id']            = 'str notNull minLength|7 maxLength|40';
        $props['title']               = 'str notNull';
        $props['message']             = 'text';
        $props['author_email']        = 'str notNull';
        $props['authored_date']       = 'dateTime notNull';
        $props['ox_user_id']          = 'ref class|CMediusers back|gitlab_user_commits';
        $props['ox_gitlab_branch_id'] = 'ref class|CGitlabBranch notNull back|gitlab_branch_commits autocomplete|_view';
        $props['web_url']             = 'str notNull';
        $props['type']                = 'str';
        $props['_types_list_multi']   = 'set list|' . implode('|', self::TYPES);

        return $props;
    }

    /**
     * Formats data from Gitlab Commits API resource to valid object specs data
     *
     * @param array $resource
     *
     * @return array
     * @throws Exception
     */
    public static function formatResource(array $resource): array
    {
        if (array_key_exists('authored_date', $resource)) {
            $resource['authored_date'] = CGitlabManager::convertGitlabDate($resource['authored_date']);
        }
        if (array_key_exists('title', $resource)) {
            $resource['title'] = iconv(
                'UTF-8',
                'ISO-8859-1//TRANSLIT//IGNORE',
                addslashes($resource['title'])
            );
        }
        if (array_key_exists('message', $resource)) {
            $resource['message'] = iconv(
                'UTF-8',
                'ISO-8859-1//TRANSLIT//IGNORE',
                addslashes($resource['message'])
            );
        }

        return $resource;
    }

    /**
     * Returns a valid commit type or an empty string
     *
     * @return string
     */
    public function determineType(): string
    {
        $pos  = strpos($this->title, ':');
        $type = ($pos !== false && $pos < 10) ? strtolower(trim(substr($this->title, 0, $pos))) : "";

        return $this->type = in_array($type, self::TYPES) ? $type : "";
    }

    /**
     * Loads the linked Gitlab project object
     *
     * @return CMediusers|CStoredObject
     * @throws Exception
     */
    public function loadRefUser(): CMediusers
    {
        return $this->_ref_user = $this->loadFwdRef('ox_user_id', true);
    }

    /**
     * Load the branch a commit is present on
     *
     * @return CGitlabBranch|CStoredObject
     * @throws Exception
     */
    public function loadRefBranch()
    {
        return $this->_ref_branch = $this->loadFwdRef('ox_gitlab_branch_id', true);
    }

    /**
     * Load the project a commit is part of
     *
     * @return false|CGitlabProject|CStoredObject
     * @throws Exception
     */
    public function loadRefProject()
    {
        $branch = $this->loadRefBranch();
        if ($branch instanceof CGitlabBranch && !empty($branch->ox_gitlab_project_id)) {
            return $this->_ref_project = $branch->loadRefGitlabProject();
        }

        return false;
    }

    /**
     * @return CTaskingTicket[]|CStoredObject[]
     * @throws Exception
     */
    public function loadRefTaskingTickets(): array
    {
        $tasks = $this->loadBackRefs("tasking_ticket_commit");
        /** @var CTaskingTicketCommit $task */
        foreach ($tasks as $task) {
            $task->loadRefTaskingTicket();
        }

        return $this->_ref_tasks = CMbArray::pluck($tasks, '_ref_task');
    }

    /**
     * Returns all committer emails
     *
     * @param string $field
     *
     * @return array|false
     * @throws Exception
     */
    public function getAllAuthorEmails(string $field = 'author_email')
    {
        return $this->getDS()->loadColumn("SELECT DISTINCT `" . $field . "` FROM `" . $this->_spec->table . "`;");
    }
}

