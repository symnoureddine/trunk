{{*
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{mb_script module=$m script=timeline_implement ajax=$ajax}}
{{mb_script module=system script=system_timeline ajax=$ajax}}
{{mb_default var=print value=false}}

{{*
Don't forget to implement:
- TimelineImplement.refreshResume({string|string[]|null} canonical_menu_name);
which will load, refresh, filter menus
- TimelineImplement.selectPractitioner({int} base_id, {string[]} types, {int} filter_user_id);
which will filter the timeline by the user (filter_user_id)
base_id is whatever id you use to select your timeline data (patient_id, stay_id ...)
*}}

{{unique_id var=timeline_id}}

<script>
    Main.add(function () {
      var timeline_container = $('timeline-{{$timeline_id}}');
      ViewPort.SetAvlHeight(timeline_container, 1.0);
      if (timeline_container.getBoundingClientRect().height < 350) {
        timeline_container.style.height = 'auto';
      }

      SystemTimeline.timeline_id = '{{$timeline_id}}';
    });
</script>

<div class="timeline_menu timeline_menu_design">
  {{mb_include module=system template=timeline/menu_timeline}}
</div>

{{mb_include module=system template=timeline/filters_timeline}}

<div id="timeline-{{$timeline_id}}" class="main-timeline" style="overflow: auto;" onscroll="SystemTimeline.onScroll(this);">
<ul class="timeline">
  {{foreach from=$timeline->getTimeline() key=year item=date_month}}
    {{foreach from=$date_month key=month item=date_item}}
      {{foreach name=dates from=$date_item key=date item=element}}
      <li class="timeline_{{if $date > $today}}futur{{elseif $date == $today}}present{{else}}past{{/if}} evenement-span
            {{foreach from=$element key=type item=context}}
              view-{{$type}}
            {{/foreach}}
            ">
        <time class="timeline_time" datetime="{{$date}}" title="{{$date|date_format:$conf.longdate}}">
          {{if $date != 'undated'}}
            {{if $smarty.foreach.dates.first}}
              <span class="timeline_year" id="timeline-year-{{$year}}-{{$month}}">{{$year}}</span>
            {{/if}}
            <span class="timeline_day">{{$date|progressive_date_day}}</span>
            <span class="timeline_month">{{$date|progressive_date_month:"%B"}}</span>
          {{else}}
            {{if $smarty.foreach.dates.first}}
              <span class="timeline_year" id="timeline-year-{{$year}}-{{$month}}">{{tr}}undated{{/tr}}</span>
            {{/if}}
          {{/if}}
        </time>

        {{foreach from=$element key=type item=context}}

          {{assign var=category value=0}}
          {{foreach from=$menu_classes item=_class}}
            {{if $_class->getCanonicalName() == $type}}
              {{assign var=category value=$_class}}
              {{assign var=category_class value=$_class->getCategoryColorValue()}}
            {{/if}}
          {{/foreach}}

          <li class="evenement-span view-{{$type}} evenement-span-{{$category_class}}">
            <div style="border: 0;">
              <div class="timeline_icon" data-year="{{$year}}" data-month="{{$month}}"
                   onclick="TimelineImplement.refreshResume({{if $selected_menus|@count > 1}}['{{$type}}']{{/if}});">
                <i class="{{$category->getLogo()}}"></i>
              </div>
              {{if !$print}}
                <div id="{{$type}}-{{$date}}-actions" class="tooltip timeline-event-actions" style="display:none;">
                  <div class="title {{$type}}">
                    {{if $type == 'programme'}}
                        {{tr}}CTimelineCabinet-Current pathology|pl{{/tr}}
                    {{else}}
                        {{$category->getCanonicalName()}}
                    {{/if}}
                  </div>
                </div>
              {{/if}}
            </div>
            <div class="timeline_label timeline_label_{{$type}}" style="page-break-inside: avoid;">
                {{foreach from=$context item=list name="list"}}
                    {{mb_include module=$m template="timeline/inc_timeline_element" type=$type list=$list}}
                    {{if !$smarty.foreach.list.last}}
                        <tr>
                            <td colspan="2">
                                <hr class="item_separator"/>
                            </td>
                        </tr>
                    {{/if}}
                {{/foreach}}
            </div>
          </li>
        {{/foreach}}
        </li>
      {{/foreach}}
    {{/foreach}}
  {{/foreach}}
</ul>

<div id="timeline-bottom-space"></div>
</div>
