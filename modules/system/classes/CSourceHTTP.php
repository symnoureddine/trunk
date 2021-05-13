<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CMbArray;

/**
 * Source HTTP, using cURL, allowing traceability
 */
class CSourceHTTP extends CExchangeSource
{
    // Source type
    public const TYPE = 'http';

    public $source_http_id;

    public $token;
    public $_filename;
    public $_fieldname;
    public $_mimetype;
    public $_date          = "";
    public $_authorization = "";
    public $_disposition   = "";
    public $_multipart     = true;
    public $_method        = "POST";
    public $_client;
    public $_verify_peer   = true;

    /** @var array */
    public $_response_http_headers;

    /** @var int */
    public $_response_http_code;

    /** @var string */
    public $_response_http_message;

    /** @var string */
    public $_content_type;

    /** @var string */
    public $_content_md5;

    public $_OXAPI_KEY;

    /** @var string */
    public $_location_resource;

    /** @var string */
    public $_http_header_expect;

    public $_request_http_headers;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'source_http';
        $spec->key   = 'source_http_id';

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props          = parent::getProps();
        $props["token"] = "str";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function send($evenement_name = null, $tab_data = null, $name = "txtDocument")
    {
        if ($this->_filename) {
            $this->_disposition = "Content-Disposition: form-data; name=\"$name\"; filename=\"$this->_filename\"\r\n";
        }

        $content = "";

        if ($this->_multipart && $this->_method === "POST") {
            $boundary = "-------MB-BOUNDARY-" . uniqid();
            $content  = "--$boundary\r\n" .
                $this->_disposition .
                "Content-Type: $this->_mimetype\r\n\r\n" .
                $this->_data . "\r\n";

            if ($tab_data) {
                foreach ($tab_data as $key => $value) {
                    $content .= "--$boundary\r\n" .
                        "Content-Disposition: form-data; name=\"$key\"\r\n\r\n" .
                        "$value\r\n";
                }
            }

            $content         .= "--$boundary--\r\n";
            $this->_mimetype = "multipart/form-data; boundary=$boundary";
        } else {
            if ($tab_data) {
                if (is_array($tab_data)) {
                    $content = http_build_query($tab_data, null, "&");
                } else {
                    $content = $tab_data;
                }
            }
        }

        $url = $this->host;

        if ($evenement_name) {
            $url = rtrim($this->host, "/") . "/" . ltrim($evenement_name, "/");
        }

        if (($this->_method === "GET" || $this->_method === "DELETE") && $content) {
            $url .= "?$content";
        }

        $http           = new CExchangeHTTPClient($url);
        $http->_source  = $this;
        $http->loggable = $this->loggable;
        $http->setOption(CURLOPT_HEADER, true);
        $http->setOption(CURLINFO_HEADER_OUT, true);

        if (!$this->_verify_peer) {
            $http->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $http->setOption(CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($this->user && $this->password) {
            $http->setHTTPAuthentification($this->user, $this->password);
        } // Authentification par token
        else {
            if ($this->_OXAPI_KEY) {
                $http->header[] = "X-OXAPI-KEY: $this->_OXAPI_KEY";
            }
        }

        $this->_client = $http;

        if ($this->_authorization) {
            $http->header[] = $this->_authorization;
        }

        if ($this->_date) {
            $http->header[] = $this->_date;
        }
        if ($this->_content_md5) {
            $http->header[] = $this->_content_md5;
        }

        if ($this->_request_http_headers) {
            $http->header[] = $this->_request_http_headers;
        }
        if ($this->_method === "GET") {
            $full_response = $http->get();
        } else {
            if ($this->_method === "DELETE") {
                $full_response = $http->delete();
            } else {
                if ($this->_mimetype) {
                    $http->header[] = "Content-Type: $this->_mimetype";
                }
                $http->header[] = "Content-Length: " . strlen($content);

                if ($this->_http_header_expect) {
                    $http->header[] = $this->_http_header_expect;
                }

                $full_response = $this->_method == "POST" ? $http->post($content) : $http->put($content);
            }
        }

        [$headers, $response] = explode("\r\n\r\n", $full_response, 2);

        $this->_response_http_headers = $http->parseHeaders($headers);
        $this->_response_http_code    = $this->_response_http_headers["HTTP_Code"];
        $this->_response_http_message = $this->_response_http_headers["HTTP_Message"];
        $this->_content_type          = str_replace(
            " ",
            "",
            CMbArray::get($this->_response_http_headers, "Content-Type")
        );
        $this->_location_resource     = str_replace(" ", "", CMbArray::get($this->_response_http_headers, "Location"));

        $this->_acquittement = $response;
    }

    /**
     * @inheritdoc
     */
    function receive()
    {
    }

    /**
     * @inheritdoc
     */
    function isReachableSource()
    {
    }

    /**
     * @inheritdoc
     */
    function isAuthentificate()
    {
    }

    /**
     * @inheritdoc
     */
    function getResponseTime()
    {
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

}
