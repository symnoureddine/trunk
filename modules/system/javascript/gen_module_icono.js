/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

/**
 * Gestion de la génération de l'iconographie des modules
 * */
GenModuleIcono = {
  // Border radius des images
  radius: 8,
  // Taille de base des images
  size: 64,
  // Font-size en fonction de la taille du trigramme
  textSize: {
    len1: 26,
    len2: 24,
    len3: 22,
    len4: 20
  },

  /**
   * Génération d'un canvas contenant l'image d'un module
   *
   * @param container Element dans lequel créer le canvas
   * @param canvasId  Identifiant à appliquer au canvas
   * @param trigramme Texte à afficher dans l'image
   * @param color     Couleur de base pour l'image
   *
   * @returns {boolean}
   */
  genImage: function(container, canvasId, trigramme, color, callback) {
    if (!this.prepareCanvasDOM(container, canvasId)) {
      return false;
    }
    var canvas = this.Canvas.genCanvas(canvasId, this.size);
    $(container).insert(canvas);
    this.Canvas.genTile(canvas, this.size, this.radius)
      .genGradient(canvas, this.size, color)
      .genText(canvas, this.size, trigramme, color, this.textSize["len" + trigramme.length]);
    if (typeof(callback) === "function") {
      callback(canvas);
    }
  },

  prepareCanvasDOM: function(container, canvasId) {
    container = $(container);
    if (!container) {
      return false;
    }
    if ($(canvasId)) {
      $(canvasId).remove();
    }
    return this;
  },


  /**
   * Envoi de l'image CANVAS au serveur
   *
   * @param moduleId         Identifiant du module
   * @param canvasId         Identifiant du canvas à envoyer
   * @param imageToRefreshId Image de destination à rafraichir
   * @param buttonToLock     Bouton d'envoi à désactiver
   * @param callback         Fonction retour
   */
  upload: function(moduleId, canvasId, imageToRefreshId, buttonToLock, callback) {
    $(buttonToLock).disable()
      .addClassName('loading')
      .removeClassName('upload');
    new Url('system', 'ajax_upload_icono')
      .addParam('image', $(canvasId).toDataURL())
      .addParam('module_id', moduleId)
      .requestJSON(
        function(response) {
          $(buttonToLock).enable()
            .addClassName('upload')
            .removeClassName('loading');
          if (response === '0') {
            return;
          }
          $(imageToRefreshId).src = response + '?' + new Date().getTime();
          if (callback && typeof(callback) === 'function') {
            callback();
          }
        }
      );
  },

  /**
   * Envoi d'images en masse
   *
   * @param modulesId Liste des modules à envoyer
   */
  uploadAll: function(modulesId) {
    $('button_module_all').disable()
      .addClassName('loading')
      .removeClassName('upload');
    modulesId = modulesId.split(',');
    this.uploadRec(modulesId, 0);
  },

  /**
   * Envoie d'image récursif
   *
   * @param modulesId Liste des modules à envoyer
   * @param i         Index courrant d'envoi
   */
  uploadRec: function(modulesId, i) {
    if (i >= modulesId.length) {
      $('button_module_all').enable()
        .addClassName('upload')
        .removeClassName('loading');
      return;
    }
    var moduleId = modulesId[i];
    this.upload(
      moduleId,
      $('canvas_' + moduleId),
      $('image_' + moduleId),
      $('button_module_' + moduleId),
      this.uploadRec.bind(this).curry(modulesId, i + 1)
    );
  },

  Canvas: {
    /**
     * Génération du canvas
     *
     * @param canvasId Identifiant du canvas à appliquer
     * @param size     Taille du canvas
     */
    genCanvas: function(canvasId, size) {
      return DOM.canvas(
        {
          id: canvasId,
          width: size,
          height: size
        }
      );
    },
    /**
     * Génération de la tuile de fond d'un canvas
     *
     * @param canvas Canvas
     * @param size   Taille du canvas
     * @param radius Radius à appliquer
     *
     * @returns {GenModuleIcono.canvas}
     */
    genTile: function(canvas, size, radius) {
      var context = canvas.getContext('2d');
      context.beginPath();

      context.moveTo(radius, 0);
      context.lineTo(size - radius, 0);
      context.quadraticCurveTo(size, 0, size, radius);
      context.lineTo(size, size - radius);
      context.quadraticCurveTo(size, size, size - radius, size);
      context.lineTo(radius, size);
      context.quadraticCurveTo(0, size, 0, size - radius);
      context.lineTo(0, radius);
      context.quadraticCurveTo(0, 0, radius, 0);
      context.closePath();

      return this;
    },
    /**
     * Génération du dégradé de fond d'un Canvas
     *
     * @param canvas Canvas
     * @param color  Couleur de base
     *
     * @returns {GenModuleIcono.canvas}
     */
    genGradient: function(canvas, size, color) {
      var context = canvas.getContext('2d');
      var gradient = context.createRadialGradient(0,0, 0, 0, 0, Math.sqrt(Math.pow(size, 2) * 2));
      gradient.addColorStop(0, this.getDarkerColor(color));
      gradient.addColorStop(1, this.getDarkerColor(color, 2));
      context.fillStyle = gradient;
      context.fill();

      return this;
    },
    /**
     * Génération du text d'un canvas
     *
     * @param canvas   Canvas
     * @param size     Taille du canvas
     * @param text     Texte à appliquer
     * @param color    Couleur de base du canvas
     * @param textSize Taille de text
     *
     * @returns {GenModuleIcono.canvas}
     */
    genText: function(canvas, size, text, color, textSize) {
      var context = canvas.getContext('2d');
      var gradient = context.createRadialGradient(0,0, 0, 0, 0, Math.sqrt(Math.pow(size, 2) * 2));
      gradient.addColorStop(0, this.getLighterColor(color, 2));
      gradient.addColorStop(1, this.getLighterColor(color));
      context.fillStyle = gradient;
      context.font = textSize + "px Arial";
      context.textAlign="center";
      context.textBaseline = "middle";

      context.shadowOffsetX = 1;
      context.shadowOffsetY = 1;
      context.shadowColor = this.getDarkerColor(color, 3);
      context.shadowBlur = 5;

      context.fillText(text, size/2, size/2);
      return this;
    },


    /**
     * Génération d'une couleur plus sombre
     *
     * @param color Couleur de base
     * @param pow   Appliquer l'assombrissement X fois
     * @param delta Puissance de l'assombrissement à appliquer
     *
     * @returns {string}
     */
    getDarkerColor: function(color, pow, delta) {
      var l = (delta ? delta : 50) * (pow ? pow : 1);
      var r = Math.min(Math.max((parseInt(color.substr(1, 2), 16) - l), 0), 255).toString(16);
      var g = Math.min(Math.max((parseInt(color.substr(3, 2), 16) - l), 0), 255).toString(16);
      var b = Math.min(Math.max((parseInt(color.substr(5, 2), 16) - l), 0), 255).toString(16);
      return "#" + (r.length < 2 ? "0" : "") + r + (g.length < 2 ? "0" : "") + g + (b.length < 2 ? "0" : "") + b;
    },

    /**
     * Génération d'une couleur plus claire
     *
     * @param color Couleur de base
     * @param pow   Appliquer l'éclaircissement X fois
     *
     * @returns {string}
     */
    getLighterColor: function(color, pow) {
      return this.getDarkerColor(color, pow,-75);
    },
  },

  TableView: {
    currentFilters: [],
    filter: function(field, index) {
      var value = $V(field).toUpperCase();
      this.currentFilters[index] = value;
      var lines = field.up('table').select('tbody tr');
      for (var i = 0; i < lines.length; i++) {
        var _line = lines[i];
        var _esc = false;
        _line.show();
        for (var j = 0; j < this.currentFilters.length; j++) {
          var _filter = this.currentFilters[j];
          if (_esc ||!_filter || _filter === "") {
            continue;
          }
          var _content = _line.select('td')[j].textContent.toUpperCase();
          if (_content.indexOf(_filter) === -1) {
            _line.hide();
            _esc = true;
          }
        }
      }
    }
  }
};
