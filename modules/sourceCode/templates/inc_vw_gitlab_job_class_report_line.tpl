{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

{{* Namespace *}}
<td class="text">
  <b>{{mb_value object=$line field=namespace}}</b>
</td>

{{* Lines covered *}}
<td class="text">
  {{mb_value object=$line field=lines_covered}}
</td>

{{* Lines all *}}
<td class="text">
  {{mb_value object=$line field=lines_all}}
</td>

{{* Coverage *}}
<td style="text-align:center;">
  {{mb_include
        module=system
        template=inc_progress_bar
        numerator=$line->lines_covered
        denominator=$line->lines_all
        percentage=$line->coverage
        theme="modern"
        precision=2
  }}
</td>
