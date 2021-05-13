{{*
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
  <title>Code de validation Mediboard</title>
  <style type="text/css">    .ReadMsgBody {
      width: 100%;
      background-color: #ffffff;
    }

    .ExternalClass {
      width: 100%;
      background-color: #ffffff;
    }

    .ExternalClass, .ExternalClass p, .ExternalClass span, .ExternalClass font, .ExternalClass td, .ExternalClass div {
      line-height: 100%;
    }

    html {
      width: 100%;
    }

    body {
      -webkit-text-size-adjust: none;
      -ms-text-size-adjust: none;
      margin: 0;
      padding: 0;
    }

    table {
      border-spacing: 0;
      border-collapse: collapse;
      table-layout: fixed;
      margin: 0 auto;
    }

    table table table {
      table-layout: auto;
    }

    img {
      display: block !important;
      over-flow: hidden !important;
    }

    table td {
      border-collapse: collapse;
    }

    .yshortcuts a {
      border-bottom: none !important;
    }

    a {
      color: #6ec8c7;
      text-decoration: none;
    }

    .textbutton a {
      font-family: 'open sans', arial, sans-serif !important;
      color: #ffffff !important;
    }

    .preference-link a {
      color: #6ec8c7 !important;
      text-decoration: underline !important;
    }

    @media only screen and (max-width: 640px) {
      body {
        width: auto !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table600] {
        width: 450px !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table-inner] {
        width: 90% !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table2-2] {
        width: 47% !important;
        text-align: center !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table1-3] {
        width: 29% !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table3-1] {
        width: 64% !important;
        text-align: left !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=table-full] {
        width: 100% !important;
        text-align: center !important;
        background: none !important;
      }
    }

    @media only screen and (max-width: 640px) {
      img[class=img1] {
        width: 100% !important;
        height: auto !important;
      }
    }

    @media only screen and (max-width: 640px) {
      td[class=hide] {
        max-height: 0px !important;
        height: 0px !important;
      }
    }

    @media only screen and (max-width: 640px) {
      table[class=fade] {
        background: none !important;
      }
    }

    @media only screen and (max-width: 479px) {
      body {
        width: auto !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table600] {
        width: 290px !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table-inner] {
        width: 82% !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table2-2] {
        width: 100% !important;
        text-align: center !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table1-3] {
        width: 100% !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table3-1] {
        width: 100% !important;
        text-align: center !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=table-full] {
        width: 100% !important;
        text-align: center !important;
        background: none !important;
      }
    }

    @media only screen and (max-width: 479px) {
      img[class=img1] {
        width: 100% !important;
        height: auto !important;
      }
    }

    @media only screen and (max-width: 479px) {
      td[class=hide] {
        max-height: 0px !important;
        height: 0px !important;
      }
    }

    @media only screen and (max-width: 479px) {
      table[class=fade] {
        background: none !important;
      }
    }</style>
</head>
<body bgcolor="#414a51">
<table bgcolor="#414a51" background="images/background.png" style="background-size:cover; background-position:top;" width="100%"
       border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td height="50"></td>
  </tr>
  <tr>
    <td align="center">
      <table style=" box-shadow:0px 3px 0px #ccd5dc; border-radius:6px;" bgcolor="#FFFFFF" class="table600" width="500" border="0"
             align="center" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center">
            <table align="center" class="table-inner" width="440" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td height="0"></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" bgcolor="#f3f3f3">
            <table class="table-inner" width="440" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td height="35"></td>
              </tr>                <!--title-->
              <tr>
                <td mc:edit="title" align="center"
                    style="font-family: 'Open Sans', Arial, sans-serif; color:#6f6f6e; font-size:26px; letter-spacing:2px;font-weight: bold;">
                  <span style="font-family:arial,helvetica neue,helvetica,sans-serif">{{tr}}common-Welcome{{/tr}}</span></td>
              </tr>                <!--end title-->
              <tr>
                <td height="15"></td>
              </tr>
              <tr>
                <td align="center">
                  <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="center">
                        <table width="5" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="5" height="5" bgcolor="#6ec8c7" style="border-radius:10px;"></td>
                          </tr>
                        </table>
                      </td>
                      <td width="15"></td>
                      <td align="center">
                        <table width="5" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="5" height="5" bgcolor="#6ec8c7" style="border-radius:10px;"></td>
                          </tr>
                        </table>
                      </td>
                      <td width="15"></td>
                      <td align="center">
                        <table width="5" border="0" align="center" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="5" height="5" bgcolor="#6ec8c7" style="border-radius:10px;"></td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td height="35"></td>
              </tr>
              <tr>
                <td align="center">
                  <table class="table-inner" width="440" border="0" align="center" cellpadding="0" cellspacing="0">
                    <!--image-->
                    <tr>
                      <td align="center" style="line-height: 0px;">
                        <a href="#">
                          {{*<img src="images/pictures/logo.png" alt="" border="0" style="margin: 0; padding: 0;" mc:edit="image" />*}}
                        </a>
                      </td>
                    </tr>                      <!--end image-->                    </table>
                </td>
              </tr>
              <tr>
                <td height="40"></td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td height="15"></td>
        </tr>
        <tr>
          <td style="text-align : center; margin-left : auto; margin-right : auto;">
            <table>
              <tr>
                <td>
                  <!-- image etablissement-->
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td height="15"></td>
        </tr>
        <tr>
          <td align="center">
            <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td width="75" align="right" valign="top">
                  <table width="75" border="0" align="right" cellpadding="0" cellspacing="0">
                    <tr>
                      <td height="25"></td>
                    </tr>
                    <tr>
                      <td height="25"></td>
                    </tr>
                  </table>
                </td>
                <td bgcolor="#f3f3f3" align="center" background="images/cta-bg.png"
                    style="background-image: url(images/cta-bg.png); background-repeat: repeat-x; background-size: auto; background-position: bottom;">
                  <!--button-->
                  <table style="border-radius:6px;" class="textbutton" width="100%" border="0" align="center" cellpadding="0"
                         cellspacing="0" bgcolor="#3bb5e8">
                    <tr>
                      <td height="2"></td>
                    </tr>
                    <tr>
                      <td mc:edit="button" height="50" align="center"
                          style="padding-left: 10px;padding-right: 10px; font-family: 'Open Sans', Arial, sans-serif; font-size: 16px;color:#FFFFFF;font-weight: bold;">
                        <a href="[url_mb]">{{tr}}common-action-Click here to access to Mediboard{{/tr}}</a></td>
                    </tr>
                    <tr>
                      <td height="2"></td>
                    </tr>
                  </table>                      <!--end button-->                  </td>
                <td width="75" align="left" valign="top">
                  <table width="75" border="0" align="left" cellpadding="0" cellspacing="0">
                    <tr>
                      <td height="25"></td>
                    </tr>
                    <tr>
                      <td height="5"></td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td height="10"></td>
        </tr>            <!--content-->

        <tr>
          <td mc:edit="preference" class="preference-link" align="center"
              style="font-family: 'Open sans', Arial, sans-serif; color:#95a5a6; font-size:13px; line-height: auto;font-style: italic; padding: 5px;">
            {{tr}}CAuthenticationFactor-msg-You receive this email because strong authentication is enabled.{{/tr}}
            <br/>
            <br/>
            {{tr}}CAuthenticationFactor-msg-Type your validation code to continue.{{/tr}}
          </td>
        </tr>

        <tr>
          <td height="10"></td>
        </tr>            <!--content-->

        <tr>
          <td align="center">
            <table align="center" class="table-inner" width="440" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td mc:edit="content" align="center"
                    style="font-family: 'Open Sans', Arial, sans-serif; color:#7f8c8d; font-size:14px;line-height: 28px;;">
                  <strong>{{tr}}CAuthenticationFactor-Your validation code:{{/tr}}</strong><br>[code]<br/>
                </td>
              </tr>
              <tr>
                <td height="10"></td>
              </tr>
            </table>
          </td>
        </tr>          <!--end content-->
        <tr>
          <td height="40"></td>
        </tr>
        <tr>
          <td height="45" align="center" bgcolor="#f4f4f4" style="border-bottom-left-radius:6px;border-bottom-right-radius:6px;">
            <table align="center" class="table-inner" width="440" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td height="10"></td>
              </tr>
              <tr>
                <td height="20"></td>
              </tr>
              <tr>
                <td height="10" mc:edit="preference" class="preference-link" align="center"
                    style="font-family: 'Open sans', Arial, sans-serif; color:#95a5a6; font-size:12px; line-height: auto;font-style: italic;">
                  {{tr}}common-msg-If you do not see the button above, please follow this link:{{/tr}}
                </td>
              </tr>
              <tr>
                <td height="10"></td>
              </tr>
              <tr>
                <td height="10" mc:edit="preference" class="preference-link" align="center"
                    style="font-family: 'Open sans', Arial, sans-serif; color:#95a5a6; font-size:12px; line-height: auto;font-style: italic;">
                  <a href="[url_mb]">[url_mb]</a></td>
              </tr>
              <tr>
                <td height="10"></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td align="center">
      <table align="center" class="table600" width="500" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td height="20"></td>
        </tr>          <!--social-->            <!--end social-->        </table>
    </td>
  </tr>
  <tr>
    <td height="40"></td>
  </tr>
</table>
</body>
</html>