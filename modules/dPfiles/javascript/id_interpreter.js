/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

IdInterpreter = window.IdInterpreter || {
  formToComplete: null,
  formUpload: null,
  currentPatient: null,
  patientId: null,
  internalFile: false,
  smallDimensions: false,

  /**
   * Open the IdInterpreter popup
   *
   * @param patientGuid    Patient guid
   * @param formToComplete Original form to complete
   */
  open : function(formToComplete, patientGuid) {
    this.formToComplete = formToComplete;
    new Url('files', 'id_interpreter')
      .addNotNullParam('patient_guid', patientGuid)
      .requestModal();
  },

  /**
   * Reset the Id Interpreter popup
   */
  reset: function() {
    Control.Modal.close();
    this.open(this.formToComplete, 'CPatient-' + (this.patientId ? this.patientId : 'none'));
  },

  /**
   * Initialize some feature with the Image form
   *
   * @param patientGuid
   * @param fileInput
   */
  init: function(fileInput, patientGuid) {
    var idModal = fileInput.up('.modal');
    this.smallDimensions = {
      width:  idModal.offsetWidth,
      height: idModal.offsetHeight,
      top:    idModal.getStyle('top'),
      left:   idModal.getStyle('left')
    };
    this.currentPatient = patientGuid;
    this.patientId = this.currentPatient.substr(this.currentPatient.indexOf('-') + 1);
    this.patientId = (this.patientId === 'none') ? false : this.patientId;
    this.formUpload = getForm('idinterpreter-upload-file');
    fileInput.on(
      'change',
      function() {
        var userAgent = window.navigator.userAgent;
        if (userAgent.indexOf('Trident') === -1 && userAgent.indexOf('MSIE') === -1) {
          // Navigateur non-IE : L'événement est déclenché avant l'arrivée en DOM de la nouvelle image
          getForm('idinterpreter-upload-file').select('button.inline-upload-trash').invoke('click');
          return true;
        }
        // Navigateur IE : L'événement est déclenché après l'arrivée en DOM de la nouvelle image
        //   => On supprime toutes les images, sauf la dernière.
        var nbImages = getForm('idinterpreter-upload-file').select('button.inline-upload-trash').length;
        //   => On supprime toutes les images présentes.
        if (nbImages > 1) {
          getForm('idinterpreter-upload-file').select('button.inline-upload-trash').each(
            function(element, index) {
              if (index === (nbImages - 1)) {
                return true;
              }
              element.click();
            }
          );
        }
      }
    );

    fileInput.up('.inline-upload-input').setStyle({right: '0%'});
  },

  /**
   * Image submitting traitment
   *
   * @param form Form submitted
   */
  submitImage: function(form) {
    this.toggleLoading($('idinterpreter-form'));

    var options = {
      useFormData: true,
      method: 'post',
      params: {
        ajax: 1
      }
    };
    options.postBody = serializeForm(form, options);
    options.contentType = 'multipart/form-data';

    new Url().requestJSON(function(patient) {
      var tmpInput = null;
      // No patient var, or an error value : error occured on the server side
      if (!patient) {
        patient = {
          error : "an_error_occured"
        }
      }
      if (patient.error) {
        SystemMessage.notify(DOM.div({
          className: 'error'
        }, $T('CIdInterpreter.' + patient.error)));
        if (!patient.continue) {
          Control.Modal.refresh();
          return false;
        }
      }
      this.toggleLoading($('idinterpreter-result'));
      var form = getForm('idinterpreter-result');

      // Fill the corresponding inputs
      for (var patientAttribute in patient) {
        if (tmpInput = form['patient_' + patientAttribute]) {
          if (patient[patientAttribute]) {
            tmpInput.checked = true;
            $V(form[patientAttribute], patient[patientAttribute]);
          }
        }
      }

      // Show the cropped picture
      if (patient.image) {
        var img = $('idinterpreter-image');
        img.src = "data:" + patient.image_mime + ";base64," + patient.image;
      }
      else {
        form["patient_image"].disabled = "disabled";
      }

      if (patient.image_cropped) {
        $('idinterpreter-show-container').update(DOM.img({
          style: "max-height: 100%; max-width: 400px",
          id: "idinterpreter-show-file",
          src: "data:" + patient.image_mime + ";base64," + patient.image_cropped
        }));
      }

      if (this.smallDimensions) {
        var idModal = form.up('.modal');
        var modalLeft = parseInt(idModal.getStyle('left')) - ((idModal.offsetWidth - this.smallDimensions.width) / 2);
        modalLeft = Math.max(0, modalLeft);
        var modalTop = parseInt(idModal.getStyle('top')) - ((idModal.offsetHeight - this.smallDimensions.height) / 2);
        modalTop = Math.max(0, modalTop);
        idModal.setStyle({left: modalLeft + "px", top: modalTop + "px"});
      }
    }.bind(this), options);
  },

  /**
   * Toggle the Loading block, and possibly an other block
   *
   * @param otherElement Other block to toggle
   */
  toggleLoading: function(otherElement) {
    $('idinterpreter-loading').toggle();
    if (otherElement) {
      otherElement.toggle();
    }
  },

  /**
   * Submitting fileds traitment (basically, fill the inputs of the initial form)
   *
   * @param form The form submitted
   */
  submitFields: function(form) {
    if (this.patientId) {
      var fileContainer = getForm('idinterpreter-update-files').down('div');
    }
    else {
      var fileContainer = DOM.div({
        id: 'formfile-container',
        style: 'display: block; text-align: center;'
      }).insert({
        top: DOM.button({
          className: 'cancel notext',
          onclick: 'this.up("div").remove();'
        }, $T('Remove')),
        bottom: DOM.span()
      });
      this.formToComplete.insert(fileContainer);
    }

    fileContainer.select('input').invoke('remove');
    // No initial form
    if (!this.formToComplete) {
      Control.Modal.close();
      return false;
    }

    // Get the checked fields
    form.select('input[type="checkbox"]:checked').each(function(checkbox) {
      if (this.formToComplete[checkbox.value]) {
        $V(this.formToComplete[checkbox.value], form[checkbox.value].value);
        $V(this.formToComplete['_source_' + checkbox.value], form[checkbox.value].value);
      }
    }.bind(this));

    // Images traitment
    if (form.patient_image.checked && fileContainer) {
      fileContainer.insert(DOM.input({
        type: "hidden",
        name: "formfile[]",
        value: "identite.jpg",
        "data-blob": "blob"
      })
        .store("blob", this.dataURItoBlob($('idinterpreter-image').src)));
    }

    var fileImg = $('idinterpreter-show-file');

    if (!this.internalFile) {
      if (fileImg) {
        fileContainer.insert(DOM.input({
          type: "hidden",
          name: "formfile[]",
          value: "Paper.jpg",
          "data-blob": "blob"
        })
          .store("blob", this.dataURItoBlob($('idinterpreter-show-file').src)));
      }
    }

    // Identity source fields
    var file_type = $V(this.formUpload.file_type);
    var type_justificatif;

    switch (file_type) {
      default:
      case 'id_card':
        type_justificatif = 'carte_identite';
        break;
      case 'passport':
        type_justificatif = 'passeport';
        break;
      case 'residence_permit':
        type_justificatif = 'doc_asile';
    }

    $V(this.formToComplete._type_justificatif, type_justificatif);

    this.formToComplete.insert(DOM.input({type: 'hidden', name: '_handle_files', value: '0'}));

    if ($V(this.formToComplete.modal) === '1') {
      if (fileImg) {
        this.formToComplete.insert(DOM.input({
          type: 'hidden',
          name: 'formfile[]',
          value: 'Paper.jpg',
          'data-blob': 'blob'
        })
          .store("blob", this.dataURItoBlob($('idinterpreter-show-file').src)));
      }
    }
    else {
      var input_file = this.formUpload.down("input[type=file][name='formfile[]']").remove();
      input_file.hide();

      this.formToComplete.insert(input_file);
    }

    if (this.patientId) {
      return onSubmitFormAjax(fileContainer.up('form'), (function() {
        this.formToComplete.onsubmit();
      }).bind(this));
    }

    var spanFile = fileContainer.down('span');
    spanFile.update(
      $T( 'CIdInterpreter.files_associated',
        fileContainer.select('input').length));

    Control.Modal.close();
    return false;
  },

  /**
   * Util function : Convert URI to Blob data
   *
   * @param dataURI
   *
   * @returns {Blob}
   */
  dataURItoBlob: function(dataURI) {
    // convert base64 to raw binary data held in a string
    var byteString = atob(dataURI.split(',')[1]);

    // write the bytes of the string to an ArrayBuffer
    var ab = new ArrayBuffer(byteString.length);
    var ia = new Uint8Array(ab);
    for (var i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }

    // write the ArrayBuffer to a blob, and you're done
    return new Blob([ab]);
  },

  /**
   * Launch a modal with the patient files
   *   Use self:patientId
   */
  showPatientFiles: function() {
    new Url("patients", "vw_all_docs")
      .addParam("patient_id", this.patientId)
      .addParam("context_guid", this.currentPatient)
      .addParam("ondblclick", "IdInterpreter.selectPatientFile")
      .requestModal("80%", "80%");
  },

  /**
   * Prepare a patient file for the IdInterpreter file form
   *   Used as callback in the Patient Files page
   *
   * @param fileId
   */
  selectPatientFile: function(fileId) {
    getForm('idinterpreter-upload-file').select('button.inline-upload-trash').invoke('click');

    var canvas = new DOM.canvas();
    var img = new Image();
    var selfFileContainer = $('idinterpreter-self-img');
    selfFileContainer.show()
      .select('img, input').invoke('remove');

    img.src = '?m=files&raw=thumbnail&document_id=' + fileId + '&document_class=CFile&thumb=0&download_raw=0';
    img.onload = function() {
      canvas.width = img.width;
      canvas.height = img.height;
      canvas.getContext('2d').drawImage(img, 0, 0, img.width, img.height);

      selfFileContainer.down('div.inline-upload-thumbnail').insert(img);
      canvas.toBlob(function(blob) {
        selfFileContainer.down('div.inline-upload-info').insert(DOM.input({
            type: "text",
            "data-blob": "blob",
            name: "formfile[]",
            value: "Image"
          })
            .store('blob', blob)
        );

        this.internalFile = true;
        Control.Modal.close();
      }.bind(this));
    }.bind(this);
  },

  /**
   * Delete the selected Patient File
   *   Hide the container, remove the elements
   */
  resetPatientFile: function() {
    var selfFileContainer = $('idinterpreter-self-img');
    selfFileContainer.hide()
      .select('img, input').invoke('remove');
    this.internalFile = false;
  }
};
