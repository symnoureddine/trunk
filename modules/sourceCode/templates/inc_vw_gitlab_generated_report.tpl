{{*
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<html xmlns="http://www.w3.org/1999/xhtml">
<body>
<table cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: white">
  <!-- HEAD-->
  <tr>
    <td align="center">
      <table cellspacing="0" cellpadding="0" border="0" width="700" class="table-bloc" style="background-color: #3F51B5">
        <tr>
          <td align="center" style="padding-top: 16px;">
            <img src="{{$infos_urls.img}}/mb.png" alt="Logo Mediboard" width="64" height="64" />
          </td>
        </tr>
        <tr>
          <td align="center" style="color: #FFFFFF; font-size: 24px; padding-top: 16px;">
            Project {{$project_name}}
          </td>
        </tr>
        <tr>
          <td align="center"
              style="font-size: 11px; color: #cacfef; letter-spacing: 0.8px; text-transform: uppercase; padding: 16px 0;">
            <img src="{{$infos_urls.img}}/branch.png" alt="Logo Mediboard" width="14" height="14"
                 style="margin-right: 2px;margin-left: 2px;" />
            {{$branch_name}}
          </td>
        </tr>
        <tr>
          <td align="center" style="color: #FFFFFF; font-size: 16px; line-height: 24px; padding-bottom: 16px;">
            From {{$since_format}} To {{$until_format}}
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- COMMITS -->
  <tr>
    <td align="center">
      <table cellspacing="0" cellpadding="0" border="0" width="700" class="table-bloc"
             style="background-color: #FAFAFA; margin: 16px 0">
        <tr>
          <td valign="top" style="font-size: 11px; color: #5f6368;" width="70%">
            <div style="font-size: 22px; line-height: 28px; color: #202124; padding: 16px;">
              Commits
              <a href="{{$infos_urls.commits}}" target="_blank">
                <img src="{{$infos_urls.img}}/external_link.png" width="15" height="auto" style="margin-left:5px;" />
              </a>
            </div>
            <table style="padding-left: 16px;">
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  TOTAL
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_repo.commits}}
                  {{if $stats.commits_percent == 0 }}
                    {{assign var=stats_commits_symbol value=''}}
                    {{assign var=stats_commits_color value='#c4c4c4'}}
                  {{elseif $stats.commits_percent > 0 }}
                    {{assign var=stats_commits_symbol value='+'}}
                    {{assign var=stats_commits_color value="#1aaa55"}}
                  {{else}}
                    {{assign var=stats_commits_symbol value=''}}
                    {{assign var=stats_commits_color value="#db3b21"}}
                  {{/if}}
                  <span title="{{ $stats.commits }} commits on last period"
                        style="background-color: {{$stats_commits_color}}; color: white; padding: 3px; margin-left: 3px;">
                    {{$stats_commits_symbol}}{{$stats.commits_percent}}%
                  </span>
                </td>
              </tr>
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Average per day
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_repo.commits_average}}
                </td>
              </tr>
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left;font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Contributors
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_repo.authors}}
                </td>
              </tr>
            </table>

          </td>
          <td align="center" style="padding: 24px 0">
            <img src="{{$infos_urls.img}}/repository.png" width="180" height="auto" />
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- MERGE REQUESTS -->
  <tr>
    <td align="center">
      <table cellspacing="0" cellpadding="0" border="0" width="700" class="table-bloc"
             style="background-color: #FAFAFA; margin: 16px 0">
        <tr>
          <td valign="top" style="font-size: 11px; color: #5f6368;" width="70%">
            <div style="font-size: 22px; line-height: 28px; color: #202124; padding: 16px;">
              Merge Requests
              <a href="{{$infos_urls.mr}}" target="_blank">
                <img src="{{$infos_urls.img}}/external_link.png" width="15" height="auto" style="margin-left:5px;" />
              </a>
            </div>
            <table style="padding-left: 16px;">
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Total
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_mr.total}}
                  {{if $stats.mr_percent == 0 }}
                    {{assign var=stats_mr_symbol value=''}}
                    {{assign var=stats_mr_color value='#c4c4c4'}}
                  {{elseif $stats.mr_percent > 0 }}
                    {{assign var=stats_mr_symbol value='+'}}
                    {{assign var=stats_mr_color value="#1aaa55"}}
                  {{else}}
                    {{assign var=stats_mr_symbol value=''}}
                    {{assign var=stats_mr_color value="#db3b21"}}
                  {{/if}}
                  <span title="{{ $stats.mr }} merge requests on last period"
                        style="background-color: {{$stats_mr_color}}; color: white; padding: 3px; margin-left: 3px;">
                    {{$stats_mr_symbol}}{{$stats.mr_percent}}%
                  </span>
                </td>
              </tr>
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Opened
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_mr.total}}
                </td>
              </tr>
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Merged
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_mr.merged}}
                </td>
              </tr>
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Closed
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_mr.closed}}
                </td>
              </tr>
            </table>

          </td>
          <td align="center" style="padding: 24px 0">
            <img src="{{$infos_urls.img}}/mr.png" width="180" height="auto" />
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- TESTS UNIT -->
  <tr>
    <td align="center">
      <table cellspacing="0" cellpadding="0" border="0" width="700" class="table-bloc"
             style="background-color: #FAFAFA; margin: 16px 0">
        <tr>
          <td valign="top" style="font-size: 11px; color: #5f6368;" width="70%">
            <div style="font-size: 22px; line-height: 28px; color: #202124; padding: 16px;">
              Tests Unit
              <a href="{{$infos_urls.jobs}}/{{$infos_tu.job_id}}" target="_blank">
                <img src="{{$infos_urls.img}}/external_link.png" width="15" height="auto" style="margin-left:5px;" />
              </a>
            </div>
            <table style="padding-left: 16px;">
              {{foreach from=$infos_tu.output key=_info item=_count}}
                <tr>
                  <th width="170"
                      style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                    {{$_info}}
                  </th>
                  <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                    {{$_count|number_format:0:',':' '}}
                    {{if $_info === 'Tests' }}
                      {{if $stats.tu_percent == 0 }}
                        {{assign var=stats_tu_symbol value=''}}
                        {{assign var=stats_tu_color value='#c4c4c4'}}
                      {{elseif $stats.tu_percent > 0 }}
                        {{assign var=stats_tu_symbol value='+'}}
                        {{assign var=stats_tu_color value="#1aaa55"}}
                      {{else}}
                        {{assign var=stats_tu_symbol value=''}}
                        {{assign var=stats_tu_color value="#db3b21"}}
                      {{/if}}
                      <span title="{{ $stats.tu }} tests on last period"
                            style="background-color: {{$stats_tu_color}}; color: white; padding: 3px; margin-left: 3px;">
                        {{$stats_tu_symbol}}{{$stats.tu_percent}}%
                      </span>
                    {{/if}}


                  </td>
                </tr>
              {{/foreach}}
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Duration
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_tu.duration}}
                </td>
              </tr>
            </table>
          </td>
          <td align="center" style="padding: 24px 0">
            <img src="{{$infos_urls.img}}/test_unit.png" width="180" height="auto">
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- COVERAGE -->
  <tr>
    <td align="center">
      <table cellspacing="0" cellpadding="0" border="0" width="700" class="table-bloc"
             style="background-color: #FAFAFA; margin: 16px 0">
        <tr>
          <td valign="top" style="font-size: 11px; color: #5f6368;" width="70%">
            <div style="font-size: 22px; line-height: 28px; color: #202124; padding: 16px;">
              Coverage
                <img src="{{$infos_urls.img}}/external_link.png" width="15" height="auto" style="margin-left:5px;" />
            </div>
            <table style="padding-left: 16px;">
              <tr>
                <th width="170"
                    style="padding: 16px 0; text-align: left; font-weight: 500; font-size: 11px; letter-spacing: 0.8px; line-height: 24px; text-transform: uppercase; color: #5f6368;">
                  Total
                </th>
                <td style="padding: 16px 0; text-align: left; font-size: 16px; color: #202124;">
                  {{$infos_tu.coverage}}%
                  {{if $stats.coverage_percent == 0 }}
                    {{assign var=stats_coverage_symbol value=''}}
                    {{assign var=stats_coverage_color value='#c4c4c4'}}
                    {{elseif $stats.coverage_percent > 0 }}
                    {{assign var=stats_coverage_symbol value='+'}}
                    {{assign var=stats_coverage_color value="#1aaa55"}}
                  {{else}}
                    {{assign var=stats_coverage_symbol value=''}}
                    {{assign var=stats_coverage_color value="#db3b21"}}
                  {{/if}}
                    <span title="{{ $stats.coverage }}% coverage on last period"
                          style="background-color: {{$stats_coverage_color}}; color: white; padding: 3px; margin-left: 3px;">
                      {{$stats_coverage_symbol}}{{$stats.coverage_percent}}%
                    </span>
                </td>
              </tr>

            </table>
          </td>
          <td align="center" style="padding: 24px 0">
            <img src="{{$infos_urls.img}}/coverage.png" width="180" height="auto">
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- COPYRIGHT -->
  <tr>
    <td align="center">
      <table width="700" style="background-color: #3F51B5;">
        <tr>
          <td align="center" style="color: white; padding: 16px;">
            Copyright &#169; OpenXtrem {{$year}}, all rights reserved.
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
