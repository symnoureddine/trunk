/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

if (!window.File.applet) {
  File.applet = {
    object_guid: null,
    debugConsole: null,
    directory: null,
    uploader: null,
    executer: null,
    url: null,
    current_list: [],
    current_list_status: [],
    extensions: null,
    modalWindow: null,
    timer: null,
    isOpen: false,
    autocompleteCat: null,
/*    appletCode: DOM.applet({id: 'uploader', name: 'yopletuploader', width: 0, height: 0,
                            code: 'org.yoplet.Yoplet.class', archive: 'includes/applets/yoplet2.jar'},
      DOM.param({name: 'action', value: ''}),
      DOM.param({name: 'url', value: document.location.href.replace(/((index\.php)?\?.*)$/, "modules/dPfiles/ajax_yoplet_upload.php")}),
      DOM.param({name: 'content', value: 'a'})
    ),*/
    debug: function(text) {
      if (!this.debugConsole) return;
      
      this.debugConsole.insert(text+"<br />")
    },
    watchDirectory: function() {
      // Lister les nouveaux fichiers
      if (File.applet.isOpen) return;
      try {
        File.applet.uploader.listFiles(File.applet.directory, "false");
      } catch(e) {
        File.applet.debug(e);
      }
    },
    uploadFiles: function() {
      var files_to_upload = $$(".upload-file:checked");
      var files = files_to_upload.pluck("value");
      
      // Ajouter chaque fichier à uploader dans la liste current_list_status
      // Mettre la case Envoi en loading pour les fichiers
      files_to_upload.each(function(elem) {
        File.applet.current_list_status.push([elem.value, 0]);
        elem.up("tr").down(".upload").addClassName("loading");
      });
      // Désactivation des boutons si un fichier au moins est coché
      if (files.length > 0) {
        getForm("addFastFile").select("button").invoke("writeAttribute", "disabled", "disabled");
      }
      var json = Object.toJSON(files);
      var rename = $V(getForm("addFastFile")._rename) || "upload";
      
      this.uploader.performUpload(rename, json);
    },
    handleUploadKO: function(result) {
      alert('L\'envoi du fichier ' + result.path + ' sur le serveur a échoué');
    },

    handleUploadOk: function(result) {
      var elem = this.modalWindow.container.select("input[type=checkbox]:checked").detect(function(n) { return n.value == result.path });
      
      if (!elem) {
        this.debug("Checkbox for '"+result.path+"' not found (handleUploadOk)");
        return; // warning
      }
      
      // Cochage de la case envoi pour le fichier
      var elem_td = elem.up("tr").down(".upload");
      elem_td.className = "tick";
      
      // Après l'upload du fichier, on peut créer le CFile
      var fast_file_form = getForm("addFastFile");
      $V(fast_file_form._checksum, result.checksum);
      $V(fast_file_form.object_class, this.object_guid.split('-')[0]);
      $V(fast_file_form.object_id, this.object_guid.split('-')[1]);
      $V(fast_file_form._file_path, result.path);
      fast_file_form.onsubmit();
    },
    handleListFiles: function(result) {
      $$(".yopletbutton").each(function(button) {
        button.disabled = "";
      });
      var list_files = $("file-list");
      list_files.update();
      File.applet.current_list = [];
      File.applet.current_list_status = [];
      var nb_files = 0;

      result.files.each(function(res, index) {
        var truncate;

        var truncate = res.path.lastIndexOf("\\");
        if (truncate == -1) {
          truncate = res.path.lastIndexOf("/") ;
        }
        truncate++;

        var base_name = res.path.substring(truncate);
        // Ajout du fichier dans la liste et dans la modale
        list_files.insert(
          DOM.tr({},
            DOM.td({},
              DOM.input({className: "upload-file", type: "checkbox", value: res.path, id: "yoplet_file_" + index, checked: 'checked'})
            ),
            DOM.td({},
              DOM.label({for: "yoplet_file_" + index}, DOM.span({}, base_name))
            ),
            DOM.td({className: "upload"}),
            DOM.td({className: "assoc"}),
            DOM.td({className: "delete"})));
        File.applet.current_list.push(res);

        nb_files ++;
      });
      
       if (nb_files > 0) {
         // On active tous les boutons upload disponibles
         $$(".yopletbutton").each(function(button) {
            button.disabled = "";
            button.style.border = "2px solid #0a0";
         });
       } else {
          $$(".yopletbutton").each(function(button) {
              button.style.border = "1px solid #888";
          });
       }
       File.applet.timer = setTimeout(File.applet.watchDirectory, 3000);
    },
    handleListFilesKO: function(result) {
      $$(".yopletbutton").each(function(button) {
        button.disabled = '';
        button.className = 'cancel';
        button.setStyle({border: "2px #f00 solid"});
        button.onclick = function() { alert('Le répertoire saisi dans vos préférences présente un problème.');};
      });
      File.applet.timer = setTimeout(File.applet.watchDirectory, 3000);
    },
    handleDeletionOK: function(result) {
      var elem = $$("input:checked").detect(function(n) { return n.value == result.path });
      elem.up("tr").down(".delete").className = "tick";
    },
  
    handleDeletionKO: function(result) {
      alert('La suppression du fichier a échoué : ' + result.path);
      var elem = this.modalWindow.container.select("input:checked").detect(function(n) { return n.value == result.path });
      
      if (!elem) {
        this.debug("Checkbox for '"+result.path+"' not found (handleDeletionKO)");
        return; // warning
      }
      
      elem.up("tr").down(".delete").className = "warning";
    },
    emptyForm: function(){
      var oForm = getForm("addFastFile");
      $V(oForm._rename, '');
      oForm.delete_auto.checked = true;
      $V(oForm.keywords_category, String.fromCharCode(8212) + " Catégorie");
      $V(oForm.file_category_id, '');
    },
    cancelModal: function() {
      // Ferme la modale en cliquant sur annuler,
      File.applet.isOpen = false;
      Control.Modal.close();
      this.emptyForm();
      File.applet.watchDirectory();
    },
    modalOpen: function(object_guid) {
      clearTimeout(File.applet.timer);
      File.applet.isOpen = true;
      this.modalWindow = Modal.open($("modal-yoplet"));
      this.modalWindow.container.setStyle({overflow: 'visible'});
      this.modalWindow.container.down('div.content').setStyle({overflow: 'visible'});
      getForm("addFastFile").select("button").invoke("writeAttribute", "disabled", null);
      this.object_guid = object_guid;
      // Mise à jour de l'object_class dans l'autocomplete des catégories
      this.autocompleteCat.url = this.autocompleteCat.url.replace(/object_class=[^&]*/, "object_class="+object_guid.split("-")[0]);
      this.modalWindow.position();
    },
    closeModal: function() {
      Control.Modal.close();
      File.applet.isOpen = false;
      this.emptyForm();
      // Clique sur Ok dans la modale,
      // alors on vide la liste des fichiers dans la modale
      // et on désactive les boutons upload
      File.applet.current_list = [];
      File.applet.current_list_status = [];
      $('file-list').update();
      $$('.yopletbutton').each(function(elem) {
          elem.disabled='disabled';
          elem.style.border = '1px solid #888';
      });
      // Pour le refresh dans les consultations
      File.refresh(this.object_guid.split("-")[1], this.object_guid.split("-")[0]);

      // Pour le refresh dans le dossier patient
      if (window.parent.reloadAfterUploadFile) {
        window.parent.reloadAfterUploadFile();
      }

      // Por le refresh dans l'édition du dossier patient
      if (window.Patient && Patient.reloadListFileEditPatient) {
        Patient.reloadListFileEditPatient("load");
      }
      File.applet.watchDirectory();
    },
    addfile_callback: function(id, args) {
      // Callback de l'upload classique des fichiers
      reloadCallback(id, args, false);

      var file_name = args["_old_file_path"].replace(/\\("|'|\\)/g, "$1");
      var elem = this.modalWindow.container.select("input:checked").detect(function(n){
        return n.value.replace(/[^\x00-\xFF]/g, "?") == file_name.replace(/\\\\/g,"\\"); // vieux hack des sous bois
      });

      if (!elem) {
        this.debug("Checkbox for '"+file_name+"' not found (addfile_callback)");
        return; // warning
      }

      var td_el = elem.up("tr").down(".assoc");

      if (id > 0 ) {
        td_el.className = 'tick';
        var file = this.current_list.detect(function(n) { return n.path.replace(/[^\x00-\xFF]/g, "?") == file_name.replace(/\\\\/g,"\\")});
        file = file.path;
        // Ajouter le status associé dans la liste des fichiers.
        var cur_status = this.current_list_status.detect(function(n) { return n[0].replace(/[^\x00-\xFF]/g, "?") == file_name.replace(/\\\\/g,"\\")});
        cur_status[1] = 1;

        // S'ils sont tous associés, alors on peut lancer la suppression
        if (this.current_list_status.all(function(n){ return n[1] == 1;})) {
          // Réactivation des boutons Annuler et Fermer
          getForm("addFastFile").select("button.reactive").invoke("writeAttribute", "disabled", null);

          // Si la suppression auto est cochée
          if (getForm("addFastFile").delete_auto.checked) {
            File.applet.uploader.performDelete(Object.toJSON(this.current_list_status.pluck("0")));
          }
        }
      } else {
          td_el.className = 'warning';
      }
    }
  };

  function watching() {
    if (File.applet.uploader) {
      var active = false;
      try {
        active = File.applet.uploader.isActive();
      } catch(e) {
        //this.debug(e);
      }

      if (!active)
        setTimeout(watching, 50);
      else {
        File.applet.debugConsole = $("yoplet-debug-console");
        File.applet.debug("File extensions: "+File.applet.extensions);
        File.applet.uploader.setFileFilters(File.applet.extensions); // case sensitive !
        File.applet.watchDirectory();
      }
    }
    else {
      File.applet.uploader = document.applets.yopletuploader;
      File.applet.directory = File.appletDirectory;
      if ((File.applet.directory.length == 2) && (File.applet.directory.charAt(0) != '/')) {
        File.applet.directory += '\\';
      }
      else if ((File.applet.directory.length > 3) &&
               ((File.applet.directory.charAt(File.applet.directory.length - 1) == '\\') ||
               (File.applet.directory.charAt(File.applet.directory.length - 1) == '/'))) {
        File.applet.directory = File.applet.directory.substr(0, File.applet.directory.length - 1);
      }
      watching();
    }
  }

  // La fonction appletCallBack ne peut pas être incluse dans l'objet File.applet
  appletCallBack = function(args) {
    // Ajouter l'url du script comme paramètre
    if (args) {
      var operation = args.evalJSON();
      var opname = operation.name;
      switch (opname) {
        case 'init': 
                watching();
                break;
        case 'listfiles':
                File.applet.handleListFiles(operation.result);
                break;
        case 'listfilesKO':
                File.applet.handleListFilesKO(operation.result);
                break;
        case 'uploadok':
                File.applet.handleUploadOk(operation.result);
                break;
        case 'uploadko':
                File.applet.handleUploadKO(operation.result);
                break;
        case 'deleteok':
                File.applet.handleDeletionOK(operation.result);
                break;
        case 'deleteko':
                File.applet.handleDeletionKO(operation.result);
                break;
        default:
                break;
      }
    }
      else {
        alert('could not parse callback message');
    }
  }
}