<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;

/**
 * Mediboard system offline page
 */
// If CApp doesn't exist, go back to the index
if (!class_exists(CApp::class)) {
  header("Location: index.php");
  die;
}

header("HTTP/1.1 503 Service Temporarily Unavailable");
header("Status: 503 Service Temporarily Unavailable");
header("Retry-After: 300");
header("Content-Type: text/html; charset=iso-8859-1");

?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="iso-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="AppFine">
    <meta name="author" content="SARL OpenXtrem">
    <link rel="icon" href="./modules/appFine/images/icon.png">
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,300italic,400italic,500,700,700italic,500italic'
          rel='stylesheet'
          type='text/css'>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <title>AppFine &mdash; Service inaccessible</title>
    <link href="./modules/appFine/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./modules/appFine/bootstrap/css/typeahead.css" rel="stylesheet">
    <link href="./style/mediboard_ext/vendor/fonts/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="./style/mediboard_ext/vendor/fonts/webfont-medical-icons/wfmi-style.min.css" rel="stylesheet">
    <link href="./modules/appFine/bootstrap/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <script src="modules/appFine/bootstrap/js/jquery.min.js"></script>
    <script src="modules/appFine/bootstrap/js/popper.js"></script>
    <script src="modules/appFine/bootstrap/js/bootstrap.min.js"></script>
    <script src="modules/appFine/bootstrap/js/hammer.min.js"></script>
    <script src="modules/appFine/bootstrap/js/ie10-viewport-bug-workaround.js"></script>
    <script src="modules/appFine/bootstrap/js/eModal.js"></script>
    <script src="modules/appFine/bootstrap/js/moment.min.js"></script>
    <script src="modules/appFine/bootstrap/js/bootstrap-typeahead.bundle.js"></script>
    <script src="modules/appFine/bootstrap/js/main.js"></script>
    <script src="modules/appFine/javascript/appFine.js"></script>
  </head>

  <body id="body" class="{{if !$app->user_id && !isset($password|smarty:nodefaults)}} signin {{/if}}" style="height : 100%;">
    <div class="row no-gutters">
      <div class="col-md-8 pl-3 hidden-sm-down" style="background: #6c98cf url(modules/appFine/images/background-appfine.jpg)">
        <div class="container vertical-center-vp">
          <div style="background: rgba(0,0,0,0.1); padding: 1.5em; color: white; " class="m-t-2">
            <h3>Bienvenue sur AppFine</h3>
            <p style="font-weight: lighter">
              Le portail patient nouvelle génération pour vous accompagner pendant votre parcours de soins.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-4 pl-3 pr-3">
        <div class="container vertical-center-vp text-md-left">
          <form class="form-signin" name="loginFrm" method="post" action="?">
            <hr class="form-signin-hr">
            <h1 class="form-signin-heading">
              <img src="modules/appFine/images/logo-yellow.svg">
            </h1>
            <hr class="form-signin-hr">
            <h3 style="text-align: center;">AppFine est momentanément indisponible</h3>
            <h3 style="text-align: center;">
              <?php echo htmlentities(CApp::$message) . "\n"; ?>
              Merci de réessayer ultérieurement.
            </h3>
            <br />
            <br />
            <div class="col-md-12 text-center">
              <button type="button" class="btn btn-primary"
                      style="background: #0275d8;"
                      onclick="document.location.reload(); return false;">
                <i class="fas fa-sync pr-2" aria-hidden="true"></i>Accéder à AppFine
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
