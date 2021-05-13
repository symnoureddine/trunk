<?php

/**
 * @package Mediboard\Rpps
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Rpps;

use DirectoryIterator;
use Exception;
use Ox\Core\CHTTPClient;
use Ox\Import\Rpps\Exception\CImportMedecinException;
use Throwable;
use ZipArchive;

/**
 * Description
 */
class CRppsFileDownloader
{
    public const DOWNLOAD_URL
        = 'https://service.annuaire.sante.fr/annuaire-sante-webservices/V300/services/extraction/PS_LibreAcces';

    /**
     * @return string
     * @throws CImportMedecinException
     */
    public function downloadRppsFiles(): string
    {
        $tmp_file = tempnam(dirname(__DIR__, 3) . '/tmp', 'med_');

        $fp = fopen($tmp_file, 'w+');

        $result = $this->getFile($fp);

        fclose($fp);

        if (!$result) {
            unlink($tmp_file);
            throw new CImportMedecinException('CRppsFileDownloader-msg-Error-File download failed');
        }

        if (!$this->extractFilesFromArchive($tmp_file)) {
            unlink($tmp_file);
            throw new CImportMedecinException('CRppsFileDownloader-msg-Error-Error while extracting files');
        }

        $this->renameExtractedFiles();

        unlink($tmp_file);

        return 'CRppsFileDownloader-msg-Info-Files downloaded and extracted';
    }

    /**
     * @param resource $fs
     *
     * @return CHTTPClient
     */
    private function initHttpClient($fs = null): CHTTPClient
    {
        $http_client = new CHTTPClient(self::DOWNLOAD_URL);

        if ($fs) {
            $http_client->setOption(CURLOPT_FILE, $fs);
        }

        // Pas de vérification de certificat car problème avec le certificat de service.annuaire.sante.fr
        $http_client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        return $http_client;
    }

    public function isRppsFileDownloadable(): bool
    {
        $http_client = $this->initHttpClient();

        try {
            $http_client->head(false);
            $is_downloadable = (bool)($http_client->getInfo(CURLINFO_HTTP_CODE) === 200);
        } catch (Throwable $e) {
            return false;
        }

        $http_client->closeConnection();


        return $is_downloadable;
    }

    /**
     * Method is protected to enable mocking
     *
     * @param resource $fp
     *
     * @return bool
     * @throws Exception
     */
    protected function getFile($fp): bool
    {
        $http_client = $this->initHttpClient($fp);

        return $http_client->get(true);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     * @throws Exception
     */
    protected function extractFilesFromArchive(string $file_path): bool
    {
        $zip = new ZipArchive();
        $zip->open($file_path);
        $success = $zip->extractTo($this->getUploadDirectory());
        $zip->close();

        return $success;
    }

    /**
     * Rename files from uploadDirectories
     *
     * @return int
     * @throws Exception
     */
    private function renameExtractedFiles(): int
    {
        $dir_it = new DirectoryIterator($this->getUploadDirectory());

        $files_modified = 0;

        while ($dir_it->valid()) {
            if (!$dir_it->isDot()) {
                $file_name = $dir_it->getFilename();

                if (strpos($file_name, 'PS_LibreAcces_Dipl_AutExerc') === 0) {
                    if (
                        rename(
                            $dir_it->getPathname(),
                            $dir_it->getPath() . DIRECTORY_SEPARATOR
                            . CExternalMedecinBulkImport::FILE_NAME_DIPLOME_AUTORISATION
                        )
                    ) {
                        $files_modified++;
                    }
                } elseif (strpos($file_name, 'PS_LibreAcces_SavoirFaire') === 0) {
                    if (
                        rename(
                            $dir_it->getPathname(),
                            $dir_it->getPath() . DIRECTORY_SEPARATOR
                            . CExternalMedecinBulkImport::FILE_NAME_SAVOIR_FAIRE
                        )
                    ) {
                        $files_modified++;
                    }
                } elseif (strpos($file_name, 'PS_LibreAcces_Personne_activite') === 0) {
                    if (
                        rename(
                            $dir_it->getPathname(),
                            $dir_it->getPath() . DIRECTORY_SEPARATOR
                            . CExternalMedecinBulkImport::FILE_NAME_PERSONNE_EXERCICE
                        )
                    ) {
                        $files_modified++;
                    }
                }
            }

            $dir_it->next();
        }

        return $files_modified;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getUploadDirectory(): string
    {
        return CExternalMedecinBulkImport::getUploadDirectory();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getPersonneExerciceFilePath(): string
    {
        return $this->getUploadDirectory() . DIRECTORY_SEPARATOR
            . CExternalMedecinBulkImport::FILE_NAME_PERSONNE_EXERCICE;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getSavoirFaireFilePath(): string
    {
        return $this->getUploadDirectory() . DIRECTORY_SEPARATOR . CExternalMedecinBulkImport::FILE_NAME_SAVOIR_FAIRE;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDiplomeExerciceFilePath(): string
    {
        return $this->getUploadDirectory() . DIRECTORY_SEPARATOR
            . CExternalMedecinBulkImport::FILE_NAME_DIPLOME_AUTORISATION;
    }
}
