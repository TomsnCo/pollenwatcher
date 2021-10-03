
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

// Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = {configuration: {}};
  }
  if (!isset(_cmd.configuration)) {
      _cmd.configuration = {};
  }
  if (init(_cmd.type) == 'info') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td><td>';
    tr += '<div class="row">';
    tr += '<div class="col-lg-8">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
    tr += '</div><div class="col-lg-1">';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '</td><td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled />';
    tr += '</td><td>';
    tr += '<input class="cmdAttr form-control input-sm" data-key="value" value="" placeholder="{{Valeur}}" disabled />';
    tr += '</td><td>';
    tr += '<input class="tooltips cmdAttr form-control input-sm" disabled data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="display:inline-block; diabled">';
    tr += '</td><td>';
    tr += '<input class="tooltips cmdAttr form-control input-sm" disabled data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="display:inline-block;">';
    tr += '</td><td>';  
    if (is_numeric(_cmd.id)) {
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
      tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
      tr += '</td>';
      tr += '<td>';
      tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
	  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    else {
      tr += '</td><td>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td></tr>';
    
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    function refreshValue(val) {
      $('#table_cmd [data-cmd_id="' + _cmd.id + '"] .form-control[data-key=value]').value(val);
    } 
    if (_cmd.id != undefined && init(_cmd.type) == 'info') {
      jeedom.cmd.execute({
        id: _cmd.id,
        cache: 0,
        notify: false,
        success: function(result) {
          refreshValue(result);
        }
      });
    }
  }
  if (init(_cmd.type) == 'action') {
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="fitwidth">';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td><td>';
    tr += '<div class="row">';
    tr += '<div class="col-lg-8">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom}}">';
    tr += '</div><div class="col-lg-1">';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '</td><td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled />';
    tr += '</td><td>';
    tr += '</td><td>';
    tr += '</td><td>';
    tr += '</td><td>';
    tr += '</td><td>';
    if (is_numeric(_cmd.id)) {
      tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
	  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
    tr += '</td></tr>';
    
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
  }
}