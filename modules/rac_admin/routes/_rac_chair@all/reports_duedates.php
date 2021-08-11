<?php
$package->cache_noStore();

global $filters;
$filters = $cms->helper('filters');
global $rac;
$rac = $cms->helper('rac');
$s = $cms->helper('strings');
$form = $rac->propWindowForm();
echo $form;
echo "<hr>";

$call = $form['call']->value();
$call = $cms->read($call);

if (!$call) {
    $cms->helper('notifications')->error('Call not found.');
    return;
}

echo "<p>Note: Due dates cannot be set until <a href='".$this->url('_rac_chair','prop_decisions')."'>decisions</a> are <a href='".$this->url('_rac_chair','finalize')."'>finalized</a>.</p>";

echo "<table style='width:100%'>";
echo "<tr><th>Proposal</th><th>Due Date</th></td>";
foreach ($call->completeSubmissions() as $prop) {
    if ($prop->finalDecision() && $prop->finalDecision()->funded() && !$prop->report()) {
        if (!$prop['report_due']) {
            echo "<tr class='highlighted'>";
        }else {
            echo "<tr>";
        }
        //submission info
        echo "<td valign='top'>";
        echo $prop->infoCell();
        echo "</td>";
        //due date
        echo "<td style='white-space:nowrap;' valign='top'>";
        if ($prop['report_due']) {
            echo $s->datetimeHTML($prop['report_due']);
        }else {
            echo "[not assigned]";
        }
        if ($prop->report()) {
            echo '<br><br><strong>Report filed:<br>'.$prop->report()->link().'</strong>';
            echo '<br>Created: '.$s->datetimeHTML($prop->report()['dso.created.date']);
            echo '<br>Modified: '.$s->datetimeHTML($prop->report()['dso.modified.date']);
        }else {
            echo '<br><a href="'.$this->url('_rac_chair', 'reports_duedates_set', ['prop'=>$prop['dso.id']]).'">[change due date]</a>';
        }
        echo '</td>';
        //end row
        echo "</tr>";
    }
}
echo "</table>";
