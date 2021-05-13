{{*
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<script>
  Main.add(function() {
    var pre = $("message-{{$key}}-xml").down('pre');
    pre.style.maxHeight = 'none';
    ViewPort.SetAvlHeight(pre, 1.0);
    
    Control.Tabs.create("message-tab-{{$key}}", true);
    var tree = new TreeView("message-{{$key}}-tree");
    tree.collapseAll();
  });
</script>

<div {{if $key != "input"}} style="display: none;" {{/if}} id="message-{{$key}}">
  <h1>{{$message->description}} ({{$message->version}}) <span class="type">{{$message->name}}</span> [{{$message->getEncoding()}}]</h1>
  
  <ul class="control_tabs" id="message-tab-{{$key}}">
    <li><a href="#message-{{$key}}-tree">{{tr}}tree{{/tr}}</a></li>
    <li><a href="#message-{{$key}}-er7-input">ER7 Input</a></li>
    <li><a href="#message-{{$key}}-er7-output">ER7 Output</a></li>
    <li><a href="#message-{{$key}}-xml">XML</a></li>
    <li><a href="#message-{{$key}}-warnings" {{if $message->_warnings_msg}} class="wrong"{{/if}}>Avertissements</a></li>
    <li><a href="#message-{{$key}}-errors" {{if $message->_errors_msg}} class="wrong" {{/if}}>Erreurs</a></li>
  </ul>

  <div id="message-{{$key}}-tree" style="display: none;">
    <ul class="hl7-tree">
      {{mb_include module=hl7 template=inc_segment_group_children segment_group=$message}}
    </ul>
  </div>
  
  <div id="message-{{$key}}-er7-input" style="display: none;">
    {{$message->data|highlight:er7}}
  </div>
  
  <div id="message-{{$key}}-er7-output" style="display: none;">
    {{$message->flatten(true)|smarty:nodefaults}}
  </div>
  
  <div id="message-{{$key}}-xml">
    {{$message->_xml|highlight:xml}}
  </div>
  
  <div id="message-{{$key}}-warnings" style="display: none;">
    {{mb_include module=hl7 template=inc_hl7v2_errors errors=$message->errors level=1}}
  </div>
  
  <div id="message-{{$key}}-errors" style="display: none;">
    {{mb_include module=hl7 template=inc_hl7v2_errors errors=$message->errors level=2}}
  </div>
</div>