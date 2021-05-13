
MediboardExt = {
  navigation : null,
  _modules : [],
  modules : [],
  tabs : [],
  smoke : null,
  tabsContainer: null,
  tabsSelecter: null,
  groupsSelecter: null,
  groupsContainer: null,
  groupsFilter: null,
  modulesRank : [],
  modulesMap : null,
  tamm_enabled: false,
  base_url: "",
  /**
   * Initialisation du header
   *
   * @param bool tammEnabled Change the menu with the TAMM appearence
   *
   * @returns {MediboardExt|boolean}
   */
  initHeader : function(tammEnabled) {
    this.smoke = $('nav-smoke');
    this.navPlusIcon = $('nav-plus-icon');
    this.header = $('nav-header');
    this.tamm_enabled = tammEnabled ? tammEnabled : false;
    if (this.smoke) {
      this.smoke.on('click', this.smokeOnClick.bind(this));
      if (this.header) {
        $('main').addClassName('me-fullpage');
        this.header.on(
          'click',
          function() {
            if (this.smoke.getStyle('opacity') === 0) {
              return;
            }
            this.smokeOnClick();
          }.bind(this)
        );
      }
    }
    if (this.navPlusIcon) {
      this.navPlusIcon.on(
        'click',
        function(event) {
          event.stopPropagation();
          this.toggleNavPlus();
        }.bind(this)
      );
      this.initSelectGroup()
        .initModuleSelecter()
        .initNavShadow()
        .initHomeLink()
        .initNotices();
    }

    var navContent = $('nav-plus-content');
    if (navContent) {
     navContent.on(
        'click',
        function (event) {
          event.stopPropagation();
        }
      );
    }
    window.addEventListener(
      'click',
      function() {
        this.toggleTogglingElements.defer()
      }.bind(this)
    );
    if (this.tamm_enabled) {
      window.editProtocoles = this.TammMenu.editProtocoles.bind(this.TammMenu);
    }
    else {
      window.editProtocoles = this.PluusMenu.editProtocoles.bind(this.TammMenu);
    }
    return this;
  },
  /**
   * Initialisation du sélecteur d'onglet
   *
   * @returns {MediboardExt}
   */
  initSelectTab: function() {
    this.tabs = [];

    this.tabsContainer = $('nav-tabs-container');
    this.tabsSelecter = $('nav-tabs-selecter');


    if (!this.tabsContainer || !this.tabsSelecter) {
      return this;
    }

    this.tabsContainer.update('');
    this.tabsSelecter.update('');
    this.tabsSelecter.removeEventListener('click', this.toggleTabs.bind(this));
    this.tabsSelecter.on(
      'click',
      function(event) {
        event.stopPropagation();
        this.toggleTabs()
      }.bind(this)
    );
    return this;
  },
  /**
   * Initialisation du sélecteur d'établissement
   *
   * @returns {MediboardExt}
   */
  initSelectGroup: function() {
    this.groupsSelecter = $('nav-groups-selecter');
    this.groupsContainer = $('nav-groups-container');
    this.groupsFilter = $('nav-groups-filter');

    if (!this.groupsContainer || !this.groupsSelecter) {
      return this;
    }
    if (this.groupsContainer.select('div').length <= 1) {
      return this;
    }
    this.groupsSelecter.removeClassName('nav-groups-unique');
    this.groupsSelecter.on(
      'click',
      function(event) {
        event.stopPropagation();
        this.toggleGroups();
      }.bind(this)
    );
    if (this.groupsFilter) {
      this.groupsFilter.on(
        'click',
        function(event) {
          event.stopPropagation();
        }.bind(this)
      );
      this.groupsFilter.down('input').on(
        'keyup',
        function(event) {
          if (event.key === "Enter") {
            var firstDisplayedGroup = this.groupsContainer.down(".group-item.displayed");
            if (firstDisplayedGroup) {
              firstDisplayedGroup.click();
              return;
            }
          }
          this.filterGroups(event.target.value);
        }.bind(this)
      )
    }
    this.groupsContainer.select('div.group-item').each(
      function(group) {
        group.on(
          'click',
          function (event) {
            event.stopPropagation();
            var url = document.location.href;
            var ancre = "";
            var ancreIndex = url.indexOf("#");
            if (ancreIndex !== -1 ) {
              ancre = url.substr(ancreIndex);
              url = url.substr(0, ancreIndex);
            }
            var matches = url.match("(\\?|&)g=[0-9]*");
            if (!matches || matches.length === 0) {
              url = url + (url.indexOf("?") > 0 ? "&" : "?") + 'g=' + this.get('group')
            }
            else {
              url = url.replace(matches[0], matches[0][0] + 'g=' + this.get('group'));
            }
            matches = url.match("(\\?|&)f=[0-9]*");
            if (!matches || matches.length === 0) {
              url = url + (url.indexOf("?") > 0 ? "&" : "?") + 'f=' + (this.get('function') ? this.get('function') : '');
            }
            else {
              url = url.replace(matches[0], matches[0][0] + 'f=' + (this.get('function') ? this.get('function') : ''));
            }
            document.location.href = url + ancre;
          }.bind(group)
        );
      }
    );
    return this;
  },
  /**
   * Initialisation des messages de warning et d'erreur généraux
   *
   * @returns {MediboardExt}
   */
  initNotices: function() {
    var notices = [this.navigation.up('#main').previous('.small-info, .small-warning, .small-error')];
    if(typeof(notices[notices.length-1]) !== 'undefined') {
      this.getPreviousNotice(notices);
       notices.invoke('addClassName', 'me-notice-header');
    }
    return this;
  },
  getPreviousNotice: function(notices) {
    var new_notice = notices[notices.length-1].previous('.small-info, .small-warning, .small-error');
    if(typeof(new_notice) !== 'undefined') {
      notices.push(new_notice);
      this.getPreviousNotice(notices);
    }
    else {
      return notices;
    }
  },
  /**
   * Retourne le premier .me-badge de container ou un nouveau si il n'y en a pas
   *
   * @param container
   * @returns Element
   */
  getBadge: function(container) {
    var badges = container.select('.me-badge');

    if (badges.length > 0) {
      var badge = badges.first();
      badge.className = 'me-badge me-tab-badge';
      return badge;
    }

    return DOM.span({className: 'me-badge me-tab-badge'});
  },
  /**
   * Ajoute ou met à jour un badge à un onglet
   *
   * @param tab_class Classe de l'onglet
   * @param count Valeur du badge
   * @param classes Classes du badge
   */
  setBadge: function(tab_class, count, classes) {
    var container = $('nav-tabs-container').select('.' + tab_class).first();
    var badge = this.getBadge(container);
    badge.addClassName(classes);
    badge.update(count);

    container.append(badge);
  },
  /**
   * Affiche un badge de notification à côté du tab selecter
   */
  activeNotify: function() {
    $('nav-tabs-badge').addClassName('display');
  },
  /**
   * Ajout d'un onglet
   *
   * @param module
   * @param tab
   * @param tab_name
   * @param selected
   *
   * @returns {MediboardExt}
   */
  addTab: function(tab_name, tab, selected, separator) {
    this.tabs.push(
      {
        url : tab,
        tab_name: tab_name,
        selected: selected,
        separator: separator
      }
    );
    return this;
  },
  /**
   * Récupération de la base url pour prendre en charges les routes legacy ET gui
   */
  getBaseUrl: function() {
    if (!this.base_url) {
      this.initBaseUrl();
    }
    return this.base_url;
  },
  /**
   * Initialisation de la base url pour prendre en charges les routes legacy ET gui
   */
  initBaseUrl: function(base) {
    base = base ? base : document.location.href;
    if (base.indexOf("/index.php") !== -1) {
      base = base.substr(0, base.indexOf("/index.php"));
    }
    if (base.indexOf("?") !== -1) {
      base = base.substr(0, base.indexOf("?"));
    }
    if (base.indexOf("/gui/") !== -1) {
      base = base.substr(0, base.indexOf("/gui/"));
    }
    if (base === "") {
      this.base_url = ".";
    }
    else {
      while (base[base.length - 1] === "/") {
        base = base.substr(0, base.length - 1);
      }
      this.base_url = base;
    }
    return this.base_url;
  },
  /**
   * Affichage du sélecteur d'onglet
   *
   * @returns {MediboardExt|boolean}
   */
  renderTab: function() {
    if (!this.tabsContainer || !this.tabsSelecter || this.tabs.length === 0) {
      return false;
    }
    var confSeparatorAdded = false;
    this.tabs.each(
      function(tab, index) {
        var url = tab.url.replace("&a=", "&tab=")
          .replace("?a=", "?tab=");
        var params = {
          href: this.getBaseUrl() + url,
          id: 'me-tab-' + index,
          className: tab.url.split('=')[2]
        };
        if (tab.selected) {
          params.className += ' selected';
          this.tabsSelecter.update(tab.tab_name);
        }
        if (!confSeparatorAdded && (tab.separator)) {
          confSeparatorAdded = true;
          this.tabsContainer.insert(
            DOM.div(
              {
                className: 'nav-separator'
              }
            )
          );
        }
        this.tabsContainer.insert(
          DOM.a(
            params,
            DOM.span({},tab.tab_name)
          )
        );
      }.bind(this)
    );
    this.tabsContainer.select('a').each(
      function(tab) {
        tab.on(
          'click',
          function(event) {
            event.stopPropagation();
          }.bind(tab)
        )
      }
    );
    this.tabsSelecter.setStyle({display: 'inline-block'});
    return this;
  },
  /**
   * Toggle du menu utilisateur
   *
   * @param force
   *
   * @returns {boolean|*}
   */
  toggleNavPlus: function(force) {
    if (force !== false) {
      this.toggleModuleSelecter(false)
        .toggleTabs(false)
        .toggleGroups(false);
    }
    if (!this.navPlusIcon) {
      return false;
    }
    var navPlusContainer = this.navPlusIcon.up('div.nav-plus');
    navPlusContainer[this.toggleFunction(force)]('displayed');
    return this.toggleSmoke();
  },
  /**
   * Toggle de l'écran de fumée
   *
   * @returns {MediboardExt}
   */
  toggleSmoke: function() {
    this.smoke[this.toggleFunction(
      this.navPlusIcon.up('div.nav-plus').hasClassName('displayed')
        || this.navigation.hasClassName('displayed')
        || (this.tabsContainer && this.tabsContainer.hasClassName('displayed'))
        || (this.groupsContainer && this.groupsContainer.hasClassName('displayed'))
    )]('displayed');
    return this;
  },
  /**
   * Fonction click de l'écran de fumée : Initialisation des menus de la structure
   */
  smokeOnClick: function() {
    return this.toggleModuleSelecter(false)
      .toggleNavPlus(false)
      .toggleGroups(false)
      .toggleTabs(false)
      .showModuleSubMenu(false);
  },
  /**
   * Toggle du sélecteur d'onglet
   *
   * @param force
   *
   * @returns {MediboardExt}
   */
  toggleTabs: function(force) {
    if (!this.tabsContainer) {
      return this;
    }
    if (force !== false) {
      this.toggleModuleSelecter(false)
        .toggleNavPlus(false)
        .toggleGroups(false);
    }
    this.tabsContainer[this.toggleFunction(force)]('displayed');
    return this.toggleSmoke();
  },
  /**
   * Toggle du sélecteur d'établissement
   *
   * @param force
   *
   * @returns {MediboardExt}
   */
  toggleGroups: function(force) {
    if (!this.groupsContainer) {
      return this;
    }
    if (force !== false) {
      this.toggleModuleSelecter(false)
        .toggleNavPlus(false)
        .toggleTabs(false);
    }
    this.groupsContainer[this.toggleFunction(force)]('displayed');
    if (this.groupsContainer.hasClassName('displayed') && this.groupsFilter) {
      this.resetFilterGroups();
      this.groupsFilter.down('input').focus();
    }
    return this.toggleSmoke();
  },
  /**
   * Filtre la liste des fonctions disponibles
   *
   * @param filter
   *
   * @returns {MediboardExt}
   */
  filterGroups: function(filter) {
    if (filter === "") {
      this.groupsContainer.select(".group-item").invoke("addClassName", "displayed");
      return this;
    }
    this.groupsContainer.select(".group-item").invoke("removeClassName", "displayed");
    this.groupsContainer.select(".group-item").each(
      function(group) {
        var groupDiv = group.down(".nav-group");
        if (groupDiv && groupDiv.textContent.toLowerCase().indexOf(filter.toLowerCase()) >= 0) {
          group.addClassName("displayed");
          return;
        }
        var functionDiv = group.down(".nav-function");
        if (functionDiv && functionDiv.textContent.toLowerCase().indexOf(filter.toLowerCase()) >= 0) {
          group.addClassName("displayed");
        }
      }
    )
  },
  /**
   * Reset du champ de recherche de group
   *
   * @returns {MediboardExt}
   */
  resetFilterGroups: function() {
    if (!this.groupsFilter) {
      return this;
    }
    this.groupsFilter.down("input").value = "";
    this.filterGroups("");
  },
  /**
   * Toggle add et remove classname
   *
   * @param force
   *
   * @returns {string}
   */
  toggleFunction: function(force) {
    return (force === true) ? 'addClassName' : ((force === false) ? 'removeClassName' : 'toggleClassName');
  },

  initNavShadow: function() {
    $$($$('.nav-subtabs').length > 0 ? '.nav-subtabs' : '.nav-header').invoke('addClassName', 'nav-shadow');
    return this;
  },

  toggleTogglingElements: function() {
    $$('.toggled').invoke('removeClassName', 'toggled');
    $$('.toggling').invoke('addClassName', 'toggled');
    $$('.toggling').invoke('removeClassName', 'toggling');
    return this;
  },

  addTogglableElement: function (element) {
    element.on(
      'click',
      function() {
        this[this.hasClassName('toggled') ? 'removeClassName' : 'addClassName']('toggling');
      }
    );
    return this;
  },
  /**
   * Ajout d'un module à afficher dans le menu de sélection d'un module
   *
   * @param moduleName  string : id du module
   * @param moduleLabel string : nom du module
   * @param selected    bool   : Etat de sélection du module
   */
  addModule: function(moduleName, moduleLabel, selected) {
    rankedModules = [
      'soins',
      'dPpatients',
      'dPbloc',
      'dPadmissions',
      'dPcabinet',
      'dPfacturation',
      'dPhospi',
      'maternite',
      'pharmacie',
      'dPplanningOp',
      'psy',
      'ssr',
      'dPsalleOp'
    ];
    rank = 0;
    if (rankedModules.indexOf(moduleName) >= 0) {
      rank = 1;
    }
    var words = moduleLabel.replace("de", "").replace("d'", "").split(" ");
    var init = words[0][0] + (words.length > 1 ? words[words.length - 1][0].toUpperCase() : "");
    var colors = this.genColorByStr(moduleLabel);
    this.modules.push(
      {
        name: moduleName,
        label: moduleLabel.unescapeHTML(),
        selected: selected,
        img: './modules/' + moduleName + '/images/icon.png',
        rank: rank,
        displayed: false,
        init: init,
        colors: colors
      }
    );
  },
  /**
   * Génération du menu de sélection d'un module
   *
   * @returns {MediboardExt}
   */
  initModuleSelecter: function() {
    $('nav-menu-icon').on(
      'click',
      function(event) {
        event.stopPropagation();
        this.toggleModuleSelecter()
      }.bind(this)
    );
    this.navigation = $$('.nav-modules')[0];
    var moduleContainer = this.navigation.down('.nav-modules-content');
    var modulesPlus = [];
    var modulesPlusMinus = [];
    var modulesMinus = [];
    var sortFunction = function(a, b) { return a.label > b.label ? 1 : -1;};
    this.modules = this.modules.sort(function(a, b) {return a.rank < b.rank ? 1 : -1;});
    for (var i = 0; i < this.modules.length; i++) {
      _module = this.modules[i];
      if (_module.rank > 0 && i < 9 && !this.tamm_enabled) {
        modulesPlus.push(_module);
      }
      else if (i < 9 && !this.tamm_enabled) {
        modulesPlusMinus.push(_module);
      }
      else {
        modulesMinus.push(_module);
      }
    }

    modulesPlus.sort(sortFunction);
    modulesPlusMinus.sort(sortFunction);
    modulesMinus.sort(sortFunction);

    for (var i = 0; i < [modulesPlus, modulesPlusMinus, modulesMinus].length; i++) {
      var currentModules = [modulesPlus, modulesPlusMinus, modulesMinus][i];
      for (var j = 0; j < currentModules.length; j++) {
        var currentModule = currentModules[j];
        if (j === 0 && i > 1) {
          moduleContainer.insert(DOM.div({className: 'nav-module-separator'}));
          this.navigation.down('.nav-modules-void').setStyle({display: 'block'});
        }
        classes = '';
        classes += i > 1 || this.tamm_enabled  ? ' nav-module-hidden' : '';
        classes += currentModule.selected ? ' selected' : '';

        var moduleContent = DOM.a(
          {
            className: 'nav-module-element-content',
            href:  '?m=' + currentModule.name
          }
        );
        var moduleElement = DOM.div(
          {
            className: 'nav-module-element nav-module-element-' + currentModule.name + ' ' + classes,
            'data-module' : currentModule.name
          },
          moduleContent
        );
        moduleContent.insert(
          DOM.img(
            {
              className: 'img-poc',
              src: './modules/' + currentModule.name + '/images/iconographie/'
                     + (Preferences.LOCALE ? Preferences.LOCALE : 'fr') + '/icon.png'
            }
          )
        );
        moduleContent.insert(DOM.span({}, currentModule.label));
        moduleElement.insert(DOM.div({className: 'nav-module-plus','data-module' : currentModule.name}));
        moduleContainer.insert(moduleElement);
      }
    }

    this.navigation.select('.nav-module-plus').each(
      function(modulePlus) {
        modulePlus.on(
          'click',
          function(event) {
            var pos = event.target.getBoundingClientRect();
            pos.x = (pos.x ? pos.x : pos.left) + (pos.width / 2);
            pos.y = (pos.y ? pos.y : pos.top) + (pos.height / 2);
            this.showModuleSubMenu(modulePlus.get('module'), pos);
          }.bind(this)
        )
      }.bind(this)
    );

    // Si l'utilisateur est sur mac
    if(this.isOnMac()) {
      $$('.nav-modules').invoke('addClassName', 'me-mac');
    }

    this.navigation.down('.nav-module-searcher').on(
      'keyup',
      this.searchModule.bind(this)
    );

    this.navigation.on(
      'click',
      this.showModuleSubMenu.curry("setRemovable").bind(this)
    );

    if (modulesMinus.length === 0) {
      this.navigation.down('.nav-modules-plus').hide();
      return this;
    }

    this.navigation.down('.nav-modules-plus').on(
      'click',
      function() {
        this.extendModuleSelecter(true);
      }.bind(this)
    );

    this.navigation.on(
      'wheel',
      function(wheelEvent) {
        this.extendModuleSelecter(false, wheelEvent);
      }.bind(this)
    );

    return this;
  },
  /**
   * Détection de l'OS client
   * @returns {boolean}
   */
  isOnMac: function() {
    return navigator.platform.match('Mac') !== null
  },
  /**
   * Traitement automatique à chaque rendu
   */
  onRendering: function() {
    this.addTextareaListeners();
    return this;
  },
  /**
   * Ajout d'un raccourcis "Soumission de formulaire" depuis les textareas (mac uniquement)
   */
  addTextareaListeners: function() {
    if (!this.isOnMac()) {
      return this
    }
    var textareas = $$("textarea:not('.me-textarea-listener')");
    for (var i = 0; i < textareas.length; i++) {
      var textarea = textareas[i];
      if (!textarea.up("form")) {
        continue;
      }
      textarea.on(
        "keydown",
        (event) => {
          var textarea = event.target;
          if ([224, 91, 93].indexOf(event.keyCode) > -1) {
            textarea.addClassName("me-textarea-listener-mac-cmd");
          }
          if (event.keyCode !== 13 || !textarea.hasClassName("me-textarea-listener-mac-cmd")) {
            return this;
          }
          textarea.removeClassName("me-textarea-listener-mac-cmd");
          event.preventDefault();
          event.stopPropagation();
          var form = textarea.up("form");
          if (form.onsubmit) {
            form.onsubmit(form);
          }
          else {
            form.submit(form);
          }
        }
      );
      textarea.on(
        "keyup",
        (event) => {
          if ([224, 91, 93].indexOf(event.keyCode) > -1) {
            event.target.removeClassName("me-textarea-listener-mac-cmd")
          }
        }
      );
    }
    textareas.invoke("addClassName", "me-textarea-listener");
    return this;
  },
  loadedMenus: [],
  loadingMenu: "",
  setRemovableOnMenuLoaded: false,
  /**
   * Affichage des onglets de modules depuis le menu des modules
   *
   * @param module Module concerné OU "setRemovable"
   * @param pos    Position du menu à afficher
   * @returns {MediboardExt}
   */
  showModuleSubMenu: function(module, pos) {
    $$('.nav-module-sub-menu.nav-module-sub-menu-removable').invoke('remove');
    $$('.nav-module-element.selected').invoke('removeClassName', 'selected');
    if (!module) {
      return this;
    }
    else if (module === "setRemovable") {
      if ($$('.nav-module-sub-menu').length === 0) {
        this.setRemovableOnMenuLoaded = true;
      }
      this.setRemovableOnMenu();
      return this;
    }
    var subMenu = DOM.div(
      {
        className: 'nav-module-sub-menu',
        style:     'left: ' + pos.x + 'px; top: ' + pos.y + 'px; display: block;'
      }
    );
    $$(".nav-module-element-" + module).invoke('addClassName', 'selectable');
    var grouptabs = this.loadedMenus.find(function(menu) { return menu.module === module });
    if (!grouptabs) {
      this.loadingMenu = module;
      $$('.nav-module-element-' + module)[0].down('.nav-module-plus')
        .addClassName('nav-module-plus-loading');
      var url = new Url()
        .requestJSON(
          function(response) {
            $$('.nav-module-element-' + module)[0].down('.nav-module-plus')
              .removeClassName('nav-module-plus-loading');
            if (this.loadingMenu !== module) {
              return;
            }

            this.loadingMenu = "";
            var data = response.data.attributes;
            if (!data) {
              return;
            }
            data = data.tabs;
            this.loadedMenus.push(
              {
                module: module,
                tabs: data
              }
            );
            this.fillSubMenu(subMenu, module, data);
            if (this.setRemovableOnMenuLoaded) {
              this.setRemovableOnMenu();
            }
            this.loadingMenu = false;
            this.setRemovableOnMenuLoaded = false;
          }.bind(this),
          {
            urlBase: this.getBaseUrl() + "/api/modules/" + module
          }
        );
      return this;
    }
    this.fillSubMenu(subMenu, module, grouptabs.tabs);
    return this;
  },
  /**
   * Applique le style Removable au menu affiché
   */
  setRemovableOnMenu: function() {
    $$('.nav-module-sub-menu').invoke('addClassName', 'nav-module-sub-menu-removable');
    $$('.nav-module-element.selectable').invoke('addClassName', 'selected');
    $$('.nav-module-element.selectable').invoke('removeClassName', 'selectable');
  },
  /**
   * Hydratation du sous menu d'onglet à ajouter à la DOM
   * @param subMenu   Menu à remplir
   * @param module    Module concerné
   * @param grouptabs Data des tabs à lire
   */
  fillSubMenu: function(subMenu, module, grouptabs) {
    var groups = Object.keys(grouptabs);
    if (!grouptabs || groups.length === 0) {
      return;
    }
    for (var i = 0; i < groups.length; i++) {
      var currentGroup = groups[i];
      if (currentGroup === "standard") {
        var tabs = Object.keys(grouptabs[currentGroup]);
        for (var j = 0; j < tabs.length; j++) {
          subMenu.insert(
            this.createSubMenuElement(
              $T("mod-" + module + "-tab-" + tabs[j]),
              grouptabs[currentGroup][tabs[j]]
            )
          );
        }
      }
      else {
        var link = grouptabs[currentGroup][Object.keys(grouptabs[currentGroup])[0]]
        if (currentGroup === "settings") {
          link = link.replace("&a=", "&tab=")
        }
        subMenu.insert(
          this.createSubMenuElement(
            $T(currentGroup),
            link
          )
        );
      }
    }
    this.navigation.insert(subMenu);
    var ua = window.navigator.userAgent;
    if (ua.indexOf('Firefox') === -1) {
      this.moveSubMenu(subMenu);
    }
  },
  /**
   * Création d'un élément de liste d'onglets du menu des modules
   * @param label Label de l'élément
   * @param url   Url du lien
   */
  createSubMenuElement: function(label, url) {
    return DOM.a(
          {
            href: this.getBaseUrl() + url
          },
          label
        );
  },
  /**
   * Déplacement du sous menu dans le cas où il dépasse du parent
   *             => Chrome : Le sous menu passe sous la scrollbar
   *             => IE & Edge : Le sous menu est visible mais non accessible en dehors du parent
   *
   * @param subMenu Sous menu
   *
   * @return {MediboardExt}
   */
  moveSubMenu: function(subMenu) {
    if ((subMenu.offsetLeft + subMenu.offsetWidth) > (this.navigation.offsetLeft + this.navigation.offsetWidth)) {
      subMenu.setStyle({left: (subMenu.offsetLeft -= subMenu.offsetWidth) + 'px'});
    }
    return this;
  },
  /**
   * Affichage / Disparition du menu de sélection d'un module
   *
   * @param force Paramètre pour forcer la disparition du menu
   * @returns {MediboardExt}
   */
  toggleModuleSelecter: function(force) {
    if (!this.navigation) {
      return this;
    }
    if (force !== false) {
      this.toggleGroups(false)
        .toggleNavPlus(false)
        .toggleTabs(false);
    }
    this.extendModuleSelecter(0);
    this.navigation[this.toggleFunction(force)]('displayed');
    if (this.navigation.hasClassName('displayed')) {
      var searcher = this.navigation.down('.nav-module-searcher');
      $V(searcher, '');
      this.searchModule({target: searcher});
      searcher.focus();
    }
    return this.toggleSmoke();
  },
  extendModuleSelecter: function(force, wheelEvent) {
    var scrollingTo = false;
    if (wheelEvent) {
      scrollingTo = wheelEvent.deltaY > 0 ? "down" : "up";
    }
    if (force === true || (scrollingTo === "down" && this.navigation.hasClassName('compact'))) {
      this.navigation.removeClassName('compact');
      this.navigation.addClassName('extended');
    }
    else if ($V(this.navigation.down('.nav-module-searcher')) === '' && (force === 0 || (scrollingTo === "up" && this.navigation.scrollTop < 1 && this.navigation.hasClassName('extended')))) {
      this.navigation.removeClassName('extended');
      this.navigation.addClassName('compact');
    }
    this.showModuleSubMenu(false);
    return this;
  },
  /**
   * Recherche un module parmis la list disponible à la vue
   *
   * @param e Evenement Onchange
   * @returns {MediboardExt}
   */
  searchModule: function(e) {
    var search = this.normalizeString($V(e.target));
    this.navigation.scrollTop = 0;
    if (search === '') {
      this.extendModuleSelecter(0);
    }
    else if (!this.navigation.hasClassName('extended')) {
      this.extendModuleSelecter(true);
    }
    if (search && e) {
      $$('.nav-module-tamm-container').invoke('hide');
      if ((e.keyCode === 13 || e.key === 'Enter' || e.code === 'Enter')
        || (e.keyCode === 18 || e.key === 'Alt' || e.code === 'AltLeft')
        || (e.keyCode === 40 || e.key === 'ArrowDown' || e.code === 'ArrowDown')
        || (e.keyCode === 38 || e.key === 'ArrowUp' || e.code === 'ArrowUp')) {
        var targetModule = null;
        this.navigation.select('.nav-module-element').each(
          function(module) {
            if (targetModule) {
              return false;
            }
            if (module.getStyle('display') !== 'none') {
              targetModule = module;
            }
          }
        );
        if ((e.keyCode === 13 || e.key === 'Enter' || e.code === 'Enter')) {
          var moduleTabs = $$('.nav-module-sub-menu-removable')[0];
          if (!moduleTabs) {
            targetModule.down('.nav-module-element-content').click();
            return this;
          }
          var selectedTab = moduleTabs.down('.selected');
          if (!selectedTab) {
            moduleTabs.select('a')[0].click();
            return this;
          }
          selectedTab.click();
        }
        else if ((e.keyCode === 18 || e.key === 'Alt' || e.code === 'AltLeft')) {
          e.preventDefault();
          targetModule.down('.nav-module-plus').click();
        }
        else if ((e.keyCode === 40 || e.key === 'ArrowDown' || e.code === 'ArrowDown')
          || (e.keyCode === 38 || e.key === 'ArrowUp' || e.code === 'ArrowUp')) {
          var moduleTabs = $$('.nav-module-sub-menu-removable')[0];
          if (!moduleTabs) {
            return this;
          }
          var selectedTab = moduleTabs.down('.selected');
          var tabs = moduleTabs.select('a');
          if (!selectedTab) {
            if (tabs.length > 0) {
              tabs[0].addClassName('selected');
            }
            return this;
          }
          else {
            var currentI = false;
            tabs.each(
              function(e, i) {
                if (e.hasClassName('selected')) {
                  currentI = i;
                  e.removeClassName('selected');
                }
              }
            );
          }
          if ((e.keyCode === 40 || e.key === 'ArrowDown' || e.code === 'ArrowDown')) {
            currentI = (currentI + 1 >= tabs.length) ? 0 : currentI + 1;
          }
          else if ((e.keyCode === 38 || e.key === 'ArrowUp' || e.code === 'ArrowUp')) {
            currentI = (currentI - 1 < 0) ? tabs.length - 1 : currentI - 1;
          }
          tabs[currentI].addClassName('selected');
        }
      }
      else {
        this.showModuleSubMenu(false);
      }
    }
    else {
      $$('.nav-module-tamm-container').invoke('show');
    }
    this.navigation.select('.nav-module-element').each(
      function(module) {
        var libelle = this.normalizeString(module.textContent);
        var key = this.normalizeString(module.get('module'));
        if (libelle.indexOf(search) > -1 || key.indexOf(search) > -1) {
          module.show();
        }
        else {
          module.hide();
        }
      }.bind(this)
    );
  },
  /**
   * Prepare a string
   *
   * @param string string String to convert
   *
   * @return string
   */
  normalizeString: function(string) {
    string = string.trim().toUpperCase();
    if (typeof(string.normalize) === 'undefined') {
      return string;
    }
    return string.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  },
  /**
   * Initialise le lien home de l'application
   *
   * @returns {MediboardExt}
   */
  initHomeLink: function() {
    var navTitle = $('nav-title');
    if (!navTitle) {
      return this;
    }
    navTitle.on(
      'click',
      function() {
        document.location.href = this.get('link');
      }
    );

    return this;
  },

  initWindow: function() {

  },

  /**
   * Génération d'une couleur et d'une ombre en fonction d'une chaine de caractères
   *
   * @param str string
   *
   * @returns {{bg: string, ts: (string)}}
   */
  genColorByStr: function(str) {
    // var init_colors = ["AA3939","AA5939","AA6C39","AA7939","AA8439","AA8E39",
    //   "AA9739","AAA039","AAAA39","91A437","7B9F35","609732","2D882D","277553",
    //   "226666","29506D","2E4272","343477","403075","4B2D73","582A72","6F256F",
    //   "882D61","983351","983351"];
    // return "#" + init_colors[(str[0].charCodeAt() * str.length)%init_colors.length];

    var r = 0;
    var g = 0;
    var b = 0;
    for (var i = 0; i < str.length; i++) {
      if (i%3 === 0) {
        r += str[i].charCodeAt();
      }
      else if (i%3 === 1) {
        g += str[i].charCodeAt();
      }
      else {
        b += str[i].charCodeAt();
      }
    }
    r = (r*r*r)%255;
    g = (g*g*g)%255;
    b = (b*b*b)%255;
    rt = Math.max(0, r-75);
    gt = Math.max(0, g-75);
    bt = Math.max(0, b-75);
    ts = "";
    for (var i = 1; i <= 35; i++) {
      ts += ((ts === "") ? "" : ", ") + i + "px " + i + "px rgb(" + rt + ", " + gt + ", " + bt + ")"
    }
    return {
      bg : "rgb(" + r + "," + g + "," + b + ")",
      ts : ts
    };
  },
  switchTheme: function() {
    App.savePref("mediboard_ext", "2", function() { document.location.reload(true); });
  },

  /*********************
   *  TAMM Specific Part
   ********************/
  TammMenu: {
    initContactCallback: function(module, tab, user, fonction, emailSupportConf) {
      new Url("oxCabinet", "ajax_vw_contact")
        .requestJSON(function(choose_action) {
        if (choose_action) {
          this.contactSupport = function() {
            new Url('messagerie','vw_messagerie')
              .addParam('contact_support_ox', 1)
              .addParam('context', "PLUUS")
              .addParam('mail_subject', $T("oxCabinet-TAMM support request") + " - " + fonction + " - " + user + " - " + $T('module-' + module + '-court') + "  / " + $T('module-' + module + '-court'))
              .redirect();
            $$("div#accounts input[name=selected_account]")[1].checked = true;
          }
        }
        else {
          this.contactSupport = function() {
            var w = window.open('','_blank','',true);
            w.location.href="mailto:" + emailSupportConf +"?subject="+ $T('oxCabinet-TAMM support request') +" - " + fonction + " - " + user + " - " + $T('module-' + module + '-court') + "  / " + $T('module-' + module + '-court');
          }
        }
      }.bind(this));
    },
    hideMenu: function() {
      MediboardExt.smokeOnClick();
    },
    editInfosPerso: function() {
      new Url("oxCabinet", "edit_info_perso")
        .modal({width: "90%", height: "90%"});
      this.hideMenu();
    },
    showAbonnement : function() {
      new Url("oxCabinet", "show_abonnement")
        .modal({width: "90%", height: "90%"});
      this.hideMenu();
    },
    showHistory : function() {
      new Url("oxCabinet", "vw_history")
        .requestModal('95%', '95%');
      this.hideMenu();
    },
    showSecretary : function() {
      new Url("oxCabinet", "vw_list_secretaries")
        .requestModal('60%', '60%');
      this.hideMenu();
    },
    contactSupport: function() {
      // void
    },
    editProtocoles: function(protocole_id) {
      new Url("prescription", "vw_protocoles")
        .addNotNullParam("protocole_id", protocole_id)
        .requestModal("100%", "100%", {showReload: true});
      this.hideMenu();
    },
    editCataloguePrescription: function() {
      new Url("prescription", "vw_edit_category")
        .modal({width: "95%", height:"95%"});
      this.hideMenu();
    },
    editStocks: function() {
      new Url("oxCabinet", "vw_stocks")
        .requestModal('500px', '300px', {showReload: true});
    },
    editCorrespondantsTAMM: function() {
      new Url("patients", "vw_correspondants")
        .addParam("all_correspondants", 1)
        .requestModal('90%', '90%');
      this.hideMenu();
    },
    editRessources: function() {
      new Url("oxCabinet", "vw_ressources")
        .addParam('tamm_mod', '1')
        .modal({width: "1200", height: "700"});
      this.hideMenu();
    },
    showNotifications: function() {
      new Url("notifications", "vw_notifications_user")
        .requestModal(1200, 700);
      this.hideMenu();
    },
    editProtocoleStructureTAMM: function() {
      new Url("patients", "vw_programmes")
        .requestModal('80%', '80%');
      this.hideMenu();
    },
    showListPatients: function() {
      new Url("patients", "vw_list_patients")
        .requestModal('95%', '95%');
      this.hideMenu();
    },
    showListVerrouDossier: function() {
      new Url("oxCabinet", "vw_list_verrou_dossiers")
        .requestModal('80%', '80%');
      this.hideMenu();
    }
  },

  /*********************
   *  PLUUS Specific Part
   ********************/
  PluusMenu: {
    initContactCallback: function(module, tab, user, fonction, emailSupportConf) {
      new Url("oxCabinet", "ajax_vw_contact")
        .requestJSON(function(choose_action) {
          if (choose_action) {
            this.contactSupport = function() {
              new Url('oxCabinet','vw_messagerie')
                .addParam('contact_support_ox', 1)
                .addParam('context', "TAMM")
                .addParam('mail_subject', $T("oxCabinet-PLUUS support request") + " - " + fonction + " - " + user + " - " + $T('module-' + module + '-court') + "  / " + $T('module-' + module + '-court'))
                .redirect();
              $$("div#accounts input[name=selected_account]")[1].checked = true;
            }
          }
          else {
            this.contactSupport = function() {
              var w = window.open('','_blank','',true);
              w.location.href="mailto:" + emailSupportConf +"?subject="+ $T('oxCabinet-PLUUS support request') +" - " + fonction + " - " + user + " - " + $T('module-' + module + '-court') + "  / " + $T('module-' + module + '-court');
            }
          }
        }.bind(this));
    },
    hideMenu: function() {
      MediboardExt.smokeOnClick();
    },
    editInfosPerso: function() {
      new Url("oxCabinet", "edit_info_perso")
        .modal({height: "90%"});
      this.hideMenu();
    },
    showSecretary : function() {
      new Url("oxCabinet", "vw_list_secretaries")
        .requestModal('60%', '60%');
      this.hideMenu();
    },
    contactSupport: function() {
      // void
    },
    editProtocoles: function(protocole_id) {
      new Url("prescription", "vw_protocoles")
        .addNotNullParam("protocole_id", protocole_id)
        .requestModal("90%", "90%", {showReload: true});
      this.hideMenu();
    },
    editCataloguePrescription: function() {
      new Url("prescription", "vw_edit_category")
        .modal({width: "95%", height:"95%"});
      this.hideMenu();
    },
    editCorrespondantsTAMM: function() {
      new Url("patients", "vw_correspondants")
        .addParam("all_correspondants", 1)
        .requestModal('90%', '90%');
      this.hideMenu();
    },
    showNotifications: function() {
      new Url("notifications", "vw_notifications_user")
        .requestModal(1200, 700);
      this.hideMenu();
    },
    editProtocoleStructureTAMM: function() {
      new Url("patients", "vw_programmes")
        .requestModal('80%', '80%');
      this.hideMenu();
    },
    showListPatients: function() {
      new Url("patients", "vw_list_patients")
        .requestModal('95%', '95%');
      this.hideMenu();
    },
    showListVerrouDossier: function() {
      new Url("oxCabinet", "vw_list_verrou_dossiers")
        .requestModal('80%', '80%');
      this.hideMenu();
    }
  },

  /*********************
   * Special field
   ********************/
  MeFormField: {
    /**
     * Crée un lien entre le fonctionnement du nouvel input et de l'ancien
     *
     * @param element_id
     * @param {string} var_true  La valeur de l'input "oui"
     * @param {string} var_false La valeur de l'input "non"
     *
     * @returns {MeFormField}
     */
    prepareFormBool: function(element_id, var_true, var_false) {
      var element = $(element_id);
      var new_input = $(element_id + '_input');
      var old_content = $(element_id + '_old_input');
      var old_input = old_content.down('input');

      var input_type = old_input.type;

      $V(new_input, this.getInputChildState(old_content, input_type, var_true, var_false));

      new_input.disabled = old_input.disabled;

      new_input.on(
        'click',
        function () {
          this.setInputChildState(old_content, input_type, var_true, var_false, $V(new_input));
        }.bind(this)
      );

      var label = element.down('label');
      if (typeof(label) !== 'undefined') {
        label.on(
          'click',
          function() {
            new_input.click();
          }
        );
      }

      this.updateNewInputState(old_content, input_type,  var_true,  var_false,  new_input);

      return this;
    },
    /**
     * Récupère l'état de l'input enfant en fonction de son type
     *
     * @param {Element} container  L'élément parent de l'input
     * @param {string}  input_type Le type de l'input (radio ou checkbox)
     * @param {string}  var_true   La valeur de l'input "oui"
     * @param {string}  var_false  La valeur de l'input "non"
     *
     * @returns {bool}
     */
    getInputChildState: function(container, input_type, var_true, var_false) {
      switch (input_type) {
        case 'radio':
          var input_oui = container.down('input[value="' + var_true + '"]');
          var input_non = container.down('input[value="' + var_false + '"]');

          if(input_oui && input_non) {
            if ($V(input_oui)) {
              return true;
            }
            if ($V(input_non)) {
              return false;
            }
            return null;
          }
          break;
        case 'checkbox':
          var checkbox = container.down('input');

          if (checkbox) {
            return $V(checkbox);
          }
          break;
        default:
          return null;
      }
    },
    /**
     * Déclenche le click sur le bon input
     *
     * @param {Element} container   L'élément parent de l'input
     * @param {string}  input_type  Le type de l'input (radio ou checkbox)
     * @param {string}  var_true    La valeur de l'input "oui"
     * @param {string}  var_false   La valeur de l'input "non"
     * @param {bool}    input_state L'état du nouvel input
     *
     * @returns {null}
     */
    setInputChildState: function(container, input_type, var_true, var_false,  input_state) {
      switch (input_type) {
        case 'radio':
          var input_oui = container.down('input[value="' + var_true + '"]');
          var input_non = container.down('input[value="' + var_false + '"]');

          if(input_oui && input_non) {
            if (input_state) {
              input_oui.click();
              break;
            }
            input_non.click();
          }
          break;
        case 'checkbox':
          var checkbox = container.down('input');
          if (input_state !== $V(checkbox) ) {
            checkbox.click();
          }
          break;
        default:
          return null;
      }
    },
    /**
     * Met à jour l'état du nouvel input lors des changements de l'ancien input
     *
     * @param {Element} container  L'élément parent de l'input
     * @param {string}  input_type Le type de l'input (radio ou checkbox)
     * @param {string}  var_true   La valeur de l'input "oui"
     * @param {string}  var_false  La valeur de l'input "non"
     * @param {Element} new_input  Le nouvel input
     *
     * @returns {null}
     */
    updateNewInputState: function(container, input_type,  var_true,  var_false, new_input) {
      switch (input_type) {
        case 'radio':
          var input_oui = container.down('input[value="' + var_true + '"]');
          var input_non = container.down('input[value="' + var_false + '"]');
          var default_onchange = input_oui.onchange ? input_oui.onchange : Prototype.emptyFunction;
          input_oui.onchange = function() {
            default_onchange.bind(input_oui)();
            var new_state = $V(input_oui);
            if (new_state) {
              $V(new_input, 1);
            }
          };
          var default_onchange = input_non.onchange ? input_non.onchange : Prototype.emptyFunction;
          input_non.onchange = function() {
            default_onchange.bind(input_non)();
            var new_state = $V(input_non);
            if (new_state) {
              $V(new_input, 0);
            }
          };
          break;
        case 'checkbox':
          var checkbox = container.down('input[type="hidden"]');
          if(!checkbox) {
            return null;
          }
          var default_onchange = checkbox.onchange ? checkbox.onchange : Prototype.emptyFunction;
          checkbox.onchange = function() {
            default_onchange.bind(checkbox)();
            $V(new_input, parseInt($V(checkbox)));
          };
          break;
        default:
          return null;
      }
    },

    /**
     * Nettoie une chaine de caractère en supprimant tous les caractères considérés comme null
     *
     * @param value - la chaine de caractère
     * @param chars - Tableau contenant les caractères null
     */
    clearValue: function(value, chars) {
      chars.each(
        function(char) {
          var regex = new RegExp(char, 'g');
          if (value) {
            value = value.replace(regex, '');
          }
        }
      );
      return value;
    },
    /**
     * Ajout d'un élément 'dirtyable' : à la modification, passe à la classe 'dirty'
     *
     * @param element
     * @param null_chars {array}
     *
     * @returns {MeFormField}
     */
    prepareFormField: function (element,  null_chars) {
      var inputs = element.select('input, textarea, select');
      var input = null;
      var i = 0;
      while (input === null && inputs.length > 0) {
        if(inputs[i].getStyle('display') !== 'none') {
          input = inputs[i];
        }
        i++;
        if (i >= inputs.length && input === null) {
          input = false;
        }
      }
      if (!input) {
        return this;
      }
      var label = element.select('>label');
      label = label.length > 0 ? label[0] : false;
      var event_callback = function () {
        var value = this.clearValue($V(input), null_chars);
        element[(value === '') ? 'removeClassName' : 'addClassName']('dirty');
      }.bind(this);

      if (label) {
        label.on(
          'click',
          function() {
            input.focus();
            // Spécial V1 : éviter l'ourverture-fermeture des calendriers (le preventDefault ne fonctionne pas)
            setTimeout(
              function() {
                input.click();
              },
              50
            );
          }
        );
      }

      if ($V(input) !== 'undefined' && $V(input) !== '') {
        element.addClassName('dirty');
      }

      if (input.tagName === "SELECT") {
        return this;
      }

      input.on('blur', event_callback);
      var default_onchange = input.onchange ? input.onchange : Prototype.emptyFunction;
      input.onchange = function() {
          default_onchange.bind(input)();
          event_callback();
      };

      return this;
    },
  }
};
