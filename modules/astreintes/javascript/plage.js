/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

PlageAstreinte = {
    module:   'astreintes',
    lastList: '',
    user_id:  '',

    modalList: null,

    showForUser: function (user_id) {
        new Url('astreintes', 'ajax_plage_astreinte').addParam('user_id', user_id).popup(800, 300);  //popup is better
    },

    loadUser: function (user_id, plage_id) {
        new Url('astreintes', 'ajax_plage_astreinte')
            .addParam('plage_id', plage_id)
            .addParam('user_id', user_id)
            .requestUpdate('vw_user');

        var user = $('u' + user_id);
        if (user) {
            user.addUniqueClassName('selected');
        }
    },

    // Select plage and open form
    edit: function (plage_id, user_id) {
        new Url('astreintes', 'ajax_edit_plage_astreinte')
            .addParam('plage_id', plage_id)
            .addParam('user_id', user_id)
            .requestUpdate('edit_plage');

        var plage = $('p' + plage_id);
        if (plage) {
            plage.addUniqueClassName('selected');
        }
    },

    refreshList: function (target_id, user_id) {
        if (PlageAstreinte.lastList || target_id) {
            if (user_id != null) {
                PlageAstreinte.user_id = user_id;
            }
            if (target_id != null) {
                PlageAstreinte.lastList = target_id;
            }
            var url = new Url('astreintes', 'vw_idx_plages_astreinte');
            url.addParam('user_id', PlageAstreinte.user_id);
            url.requestUpdate(PlageAstreinte.lastList);
        }
    },

    content: function () {
        new Url('astreintes', 'vw_planning_astreinte')
            .addParam('affiche_nom', 0)
            .requestUpdate('planningconge');
    },

    modal: function (plage_id, date, hourstart, minutestart, callback) {
        var url = new Url('astreintes', 'ajax_edit_plage_astreinte');
        url.addParam('plage_id', plage_id);
        url.addParam('date', date);
        url.addParam('hour', hourstart);
        url.addParam('minutes', minutestart);
        url.requestModal('1000px', '650px');
        url.modalObject.observe('afterClose', function () {
            if (callback) {
                callback();
            } else {
                location.reload();
            }
        });
    },

    modaleastreinteForDay: function (date) {
        var url = new Url('astreintes', 'ajax_list_day_astreinte');
        if (date) {
            url.addParam('date', date);
        }
        url.requestModal('800px');
    },


    printShifts: function (formName) {
        var oForm = getForm(formName);
        var mode = oForm.mode.value;
        var value = oForm.date.value;
        var category = oForm.category.value;
        if (oForm['type_names[]'] !== undefined && oForm['type_names[]'].selectedOptions.length) {
            var types = $A(oForm['type_names[]'].selectedOptions).pluck('value');
        }

        var url = new Url('astreintes', 'offline_list_astreinte');
        url.addParam('dialog', 1);
        url.addParam('mode', mode);
        url.addParam('date', value);
        url.addParam('category', category);
        if (types) {
            url.addParam('type_names[]', types);
        }
        url.pop(700, 600, 'Liste des astreintes');
    },

    filterCategoryCalendar: function () {
        $('category').observe('change', function (e) {
            e.target.form.submit();
        });
    },

    /**
     * Resize each event of the calendar using the screen width
     */
    resizeEvents: function () {
        var table_width = $$('table.calendar_horizontal')[0].offsetWidth;
        var cell_width = $$('table.calendar_horizontal .hoveringTd')[0].offsetWidth;

        $$('.event').forEach(function (event) {
            var max_minutes = 0;
            var hours_divider = 0;
            if (event.dataset.mode === 'day') {
                // Minutes per day
                max_minutes = 1440;
                // 1 hour for each column
                hours_divider = 1;
            } else if (event.dataset.mode === 'week') {
                // Minutes per day
                max_minutes = 10079;
                // 4 hours for each column
                hours_divider = 4;
            } else if (event.dataset.mode === 'month') {
                // Minutes per day * the amount of columns
                max_minutes = 1440 * document.querySelectorAll('.dayLabel').length;
            }

            // Compute width
            var event_width = Math.floor(event.dataset.length * table_width / max_minutes);
            event.style.width = event_width + 'px';
            event.style.minWidth = event_width + 'px';

            // Compute left css to align to the right column
            if (event.dataset.mode === 'week' || event.dataset.mode === 'day') {
                var hours_left = parseInt(event.dataset.hour);
                var minutes_left = parseInt(event.dataset.minutes) / 60;
                var left = (hours_left + minutes_left) / hours_divider * cell_width;

                event.style.left = left + 'px';
            } else if (event.dataset.mode === 'month') {
                event.style.left = event.dataset.hour + 'px';
            }
        });
    },

    /**
     * Check if the category is well set
     *
     * @param {HTMLSelectElement} select
     */
    checkIssues: (select) => {
        $$('.issue').invoke('hide');

        if (select.item(select.selectedIndex).dataset.issue === "1") {
            $$('.issue').invoke('show');
        }
    }

};
