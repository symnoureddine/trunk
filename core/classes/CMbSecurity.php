<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Exception;
use Ox\Core\FieldSpecs\CPasswordSpec;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Admin\PasswordSpecs\PasswordSpecBuilder;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Base;
use phpseclib\Crypt\DES;
use phpseclib\Crypt\Hash;
use phpseclib\Crypt\Random;
use phpseclib\Crypt\Rijndael;
use phpseclib\Crypt\RSA;
use phpseclib\Crypt\TripleDES;
use phpseclib\File\X509;

/**
 * Generic security class, uses pure-PHP library phpseclib
 */
class CMbSecurity
{
    // Ciphers
    const AES      = 1;
    const DES      = 2;
    const TDES     = 3;
    const RIJNDAEL = 4;
    const RSA      = 5;

    // Encryption modes
    const CTR = Base::MODE_CTR;
    const ECB = Base::MODE_ECB;
    const CBC = Base::MODE_CBC;
    const CFB = Base::MODE_CFB;
    const OFB = Base::MODE_OFB;

    // Hash algorithms
    const MD2     = 1;
    const MD5     = 2;
    const MD5_96  = 3;
    const SHA1    = 4;
    const SHA1_96 = 5;
    const SHA256  = 6;
    const SHA384  = 7;
    const SHA512  = 8;

    public const AMBIGUOUS_CHARACTERS = [
        'I',
        'l',
        '1',
        'O',
        'o',
        '0',
    ];

    /**
     * Generate a pseudo random string with the given minimum length
     *
     * @param int $min_length Minimum string length
     *
     * @return string
     */
    static function getRandomString($min_length)
    {
        return bin2hex(Random::string($min_length));
    }

    /**
     * Get random alphanumeric string from a given charset
     *
     * @param array $chars  Allowed characters
     * @param int   $length String length
     *
     * @return string
     * @throws Exception
     */
    public static function getRandomAlphaNumericString($chars = [], $length = 16): string
    {
        $string  = '';
        $charset = ($chars) ?: array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));

        $count = count($charset) - 1;
        for ($i = 0; $i < $length; $i++) {
            $string .= $charset[random_int(0, $count)];
        }

        return $string;
    }

    /**
     * @param int $length
     *
     * @return string
     * @throws Exception
     */
    public static function getRandomBase58String($length = 16): string
    {
        return self::getRandomAlphaNumericString(CMbString::CHARSET_BASE58, $length);
    }

    /**
     * Generate a pseudo random binary string
     *
     * @param int $length Binary string length
     *
     * @return string
     */
    static function getRandomBinaryString($length)
    {
        return Random::string($length);
    }

    /**
     * Generate an initialisation vector
     *
     * @param int $length IV length
     *
     * @return string
     */
    static function generateIV($length = 16)
    {
        return self::getRandomString($length);
    }

    /**
     * Generate a UUID
     * Based on: http://www.php.net/manual/fr/function.uniqid.php#87992
     *
     * @return string
     */
    static function generateUUID()
    {
        $pr_bits = null;
        $pr_bits = self::getRandomBinaryString(25);

        $time_low = bin2hex(substr($pr_bits, 0, 4));
        $time_mid = bin2hex(substr($pr_bits, 4, 2));

        $time_hi_and_version       = bin2hex(substr($pr_bits, 6, 2));
        $clock_seq_hi_and_reserved = bin2hex(substr($pr_bits, 8, 2));

        $node = bin2hex(substr($pr_bits, 10, 6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $time_hi_and_version = hexdec($time_hi_and_version);
        $time_hi_and_version = $time_hi_and_version >> 4;
        $time_hi_and_version = $time_hi_and_version | 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $time_low,
            $time_mid,
            $time_hi_and_version,
            $clock_seq_hi_and_reserved,
            $node
        );
    }

    /**
     * Create a Crypt object
     *
     * @param int $encryption Cipher to use (AES, DES, TDES, RIJNDAEL OR RSA)
     * @param int $mode       Encryption mode to use (CTR, ECB, CBC, CFB or OFB)
     *
     * @return AES|DES|TripleDES|Rijndael|RSA
     */
    static function getCipher($encryption = self::AES, $mode = self::CTR)
    {
        switch ($encryption) {
            case self::AES:
                return new AES($mode);

            case self::DES:
                return new DES($mode);

            case self::TDES:
                return new TripleDES($mode);

            case self::RIJNDAEL:
                return new Rijndael($mode);

            case self::RSA:
                return new RSA();

            default:
        }

        return false;
    }

    /**
     * Encrypt a text
     *
     * @param int    $encryption Cipher to use (AES, DES, TDES or RIJNDAEL)
     * @param int    $mode       Encryption mode to use (CTR, ECB, CBC, CFB or OFB)
     * @param string $key        Key to use
     * @param string $clear      Clear text to encrypt
     * @param string $iv         Initialisation vector to use
     *
     * @return bool|string
     */
    static function encrypt($encryption, $mode, $key, $clear, $iv = null)
    {
        $cipher = self::getCipher($encryption, $mode);

        if (!$cipher) {
            return false;
        }

        $cipher->setKey($key);

        switch ($mode) {
            case self::CBC:
            case self::CFB:
            case self::CTR:
            case self::OFB:
                $cipher->setIV($iv);

            default:
        }

        return base64_encode($cipher->encrypt($clear));
    }

    /**
     * Decrypt a text
     *
     * @param int    $encryption Cipher to use (AES, DES, TDES or RIJNDAEL)
     * @param int    $mode       Encryption mode to use (CTR, ECB, CBC, CFB or OFB)
     * @param string $key        Key to use
     * @param string $crypted    Cipher text to decrypt
     * @param string $iv         Initialisation vector to use
     *
     * @return bool|string
     */
    static function decrypt($encryption, $mode, $key, $crypted, $iv = null)
    {
        $cipher = self::getCipher($encryption, $mode);

        if (!$cipher) {
            return false;
        }

        $cipher->setKey($key);

        switch ($mode) {
            case self::CBC:
            case self::CFB:
            case self::CTR:
            case self::OFB:
                $cipher->setIV($iv);

            default:
        }

        return $cipher->decrypt(base64_decode($crypted));
    }

    /**
     * Global hashing function
     *
     * @param int    $algo   Hash algorithm to use
     * @param string $text   Text to hash
     * @param bool   $binary Binary or hexa output
     *
     * @return bool|string
     */
    static function hash($algo, $text, $binary = false)
    {
        $algos = [
            self::MD2     => 'md2',
            self::MD5     => 'md5',
            self::MD5_96  => 'md5-96',
            self::SHA1    => 'sha1',
            self::SHA1_96 => 'sha1-96',
            self::SHA256  => 'sha256',
            self::SHA384  => 'sha384',
            self::SHA512  => 'sha512',
        ];

        if (array_key_exists($algo, $algos)) {
            $hash        = new Hash($algos[$algo]);
            $fingerprint = $hash->hash($text);

            if (!$binary) {
                $fingerprint = bin2hex($fingerprint);
            }

            return $fingerprint;
        }

        return false;
    }

    /**
     * Filtering input data
     *
     * @param string $params Array to filter
     *
     * @return array
     */
    static function filterInput($params)
    {
        if (!is_array($params)) {
            return $params;
        }

        $patterns = [
            "/password|passphrase/i",
            "/login/i",
        ];

        $replacements = [
            ["/.*/", "***"],
            ["/([^:]*):(.*)/i", "$1:***"],
        ];

        // We replace passwords and passphrases with a mask
        foreach ($params as $_key => $_value) {
            foreach ($patterns as $_k => $_pattern) {
                if (!empty($_value) && preg_match($_pattern, $_key)) {
                    $params[$_key] = preg_replace($replacements[$_k][0], $replacements[$_k][1], $_value);
                }
            }
        }

        return $params;
    }

    /**
     * Validate the client certificate with the authority certificate
     *
     * @param String $certificate_client Client certificate
     * @param String $certificate_ca     Authority certificate
     *
     * @return bool
     */
    static function validateCertificate($certificate_client, $certificate_ca)
    {
        $x509 = new X509();

        $x509->loadX509($certificate_client);
        $x509->loadCA($certificate_ca);

        return $x509->validateSignature(X509::VALIDATE_SIGNATURE_BY_CA);
    }

    /**
     * Return the DN of the certificate
     *
     * @param String $certificate_client Client certificate
     *
     * @return String
     */
    static function getDNString($certificate_client)
    {
        $x509 = new X509();

        $x509->loadX509($certificate_client);

        // Param à 1 pour avoir un string en retour
        return $x509->getDN(1);
    }

    /**
     * Return the Issuer DN of the certificate
     *
     * @param String $certificate_client Client certificate
     *
     * @return String
     */
    static function getIssuerDnString($certificate_client)
    {
        $x509 = new X509();

        $x509->loadX509($certificate_client);

        // Param à 1 pour avoir un string en retour
        return $x509->getIssuerDN(1);
    }

    /**
     * Validate the client certificate with the current date
     *
     * @param String $certificate_client Client certificate
     *
     * @return bool
     */
    static function validateCertificateDate($certificate_client)
    {
        $x509 = new X509();

        $x509->loadX509($certificate_client);

        return $x509->validateDate();
    }

    /**
     * Return the information of certificate
     *
     * @param String $certificate_client Client certificate
     *
     * @return String[]
     */
    static function getInformationCertificate($certificate_client)
    {
        $x509 = new X509();

        return $x509->loadX509($certificate_client);
    }

    /**
     * Return the certificate serial
     *
     * @param String $certificate_client client certificate
     *
     * @return String
     */
    static function getCertificateSerial($certificate_client)
    {
        $x509 = new X509();

        $certificate = $x509->loadX509($certificate_client);

        return $certificate["tbsCertificate"]["serialNumber"]->value;
    }

    /**
     * Verify that certificate is not revoked
     *
     * @param String $certificate_client String
     * @param String $list_revoked       String
     *
     * @return bool
     */
    static function isRevoked($certificate_client, $list_revoked)
    {
        $certificate = self::getInformationCertificate($certificate_client);

        if (!$certificate) {
            return false;
        }

        $serial = self::getCertificateSerial($certificate_client);

        $x509 = new X509();
        $crl  = $x509->loadCRL($list_revoked);

        foreach ($crl["tbsCertList"]["revokedCertificates"] as $_cert) {
            if ($_cert["userCertificate"]->value === $serial) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert a private key from the pkcs12 format to the pem format
     * Returns false on failure, an array containing the cert (public key) and the private key otherwise
     *
     * @param string $pkcs12            The content of the pkcs12 file
     * @param string $pkcs12_passphrase The passphrase used for deciphering the pkcs12 file
     * @param string $passphrase        The paspshrase that protect the private key
     *
     * @return bool|array
     */
    public static function convertPKCS12ToPEM($pkcs12, $pkcs12_passphrase, $passphrase = null)
    {
        $cert_data = null;
        $result    = openssl_pkcs12_read($pkcs12, $cert_data, $pkcs12_passphrase);
        if ($result || !array_key_exists('pkey', $cert_data) || !array_key_exists('cert', $cert_data)) {
            $pkey   = null;
            $result = openssl_pkey_export($cert_data['pkey'], $pkey, $passphrase);

            return ['cert' => $cert_data['cert'], 'pkey' => $pkey, 'extracerts' => $cert_data['extracerts']];
        }

        return false;
    }

    /**
     * Generate a random password according given specification.
     *
     * @param CPasswordSpec|string|null $spec
     * @param bool                      $remove_ambiguous
     *
     * @return false|string
     * @throws Exception
     */
    public static function getRandomPassword($spec = null, bool $remove_ambiguous = false)
    {
        if ($spec instanceof CPasswordSpec) {
            $object = new $spec->className();
            $field  = $spec->fieldName;
        } else {
            // $spec is a string or NULL
            $object = new CUser();
            $field  = '_random_password';

            switch ($spec) {
                case 'strong':
                    $spec = (new PasswordSpecBuilder($object))->getStrongSpec()->getSpec($field);
                    break;

                case 'admin':
                default:
                    $spec = (new PasswordSpecBuilder($object))->getAdminSpec()->getSpec($field);
            }
        }

        $object->_specs[$field] = $spec;

        $charset = $spec->getAllowedCharset();

        if ($remove_ambiguous) {
            $charset = array_values(array_diff($charset, static::AMBIGUOUS_CHARACTERS));
        }

        // "Weak" password spec is not allowed here
        if (!$charset) {
            return false;
        }

        do {
            $object->{$field} = CMbSecurity::getRandomAlphaNumericString($charset, $spec->minLength);
        } while ($spec->checkProperty($object));

        return $object->{$field};
    }

    /**
     * Verify the signature of a message
     *
     * @param RSA    $cipher     The cipher used
     * @param string $certificat The certificate
     * @param string $message    The original message
     * @param string $signature  The generated signature
     *
     * @return bool
     */
    static function verify($cipher, $hash, $mode, $certificat, $message, $signature)
    {
        $cipher->setHash($hash);
        $cipher->setSignatureMode($mode);

        $openssl_pkey = openssl_pkey_get_public($certificat);
        $public_key   = openssl_pkey_get_details($openssl_pkey);

        $cipher->loadKey($public_key['key']);

        return $cipher->verify($message, $signature);
    }
}
