/**
 * @package Mediboard\CompteRendu
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

CKEDITOR.plugins.add('mbfields', {
  requires: ['dialog'],
  init: function(editor) {
    var me = this;
    var date = new Date();
    date = Math.round(date.getTime()/3600000);

    CKEDITOR.dialog.add('mbfields_dialog', function() {
      return {
        title : $T('CCompteRendu-plugin-action-Insert a field'),
        buttons: [
          {
            id: 'close_button',
            type: 'button',
            title: $T('Close'),
            label: $T('Close'),
            onClick: function() { CKEDITOR.dialog.getCurrent().hide(); }
          }
        ],
        minWidth : 770,
        minHeight : 340,
        contents : [
          {
            label : 'Insertion de champs',
            expand : true,
            elements : [
              {
                type : 'html',
                html : '<iframe id="' + me.name + '_iframe" src="' + me.path + 'dialogs/fields.html?' + date + '" style="width: 100%; height: 100%"></iframe>',

                onShow: function() {
                  if (document.documentMode) {
                    return;
                  }
                  var iframe = document.getElementById(me.name + '_iframe');
                  var iframeWindow = iframe.contentWindow.document;
                  var searchinput = iframeWindow.getElementById("searchinput");

                  if (searchinput) {
                    setTimeout(function() {
                      iframeWindow.body.focus();
                      searchinput.focus();
                      searchinput.select();
                    }, 100);
                  }
                }
              }
            ]
          }
        ]
      };
    });
   
    editor.addCommand('mbfields', {exec: mbfields_onclick});
    editor.ui.addButton('mbfields', {
      label:   $T('CCompteRendu-plugin-mbfields'),
      command: 'mbfields',
      icon:    this.path + 'images/mbfields.png'
    });
  }
});

function mbfields_onclick(editor) {
  editor.openDialog('mbfields_dialog');
}