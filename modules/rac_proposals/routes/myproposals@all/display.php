<?php
$package->cache_noCache();
$package['response.ttl'] = 30;

$user = $cms->helper('users')->user();

if (!$user) {
    $signin = $cms->helper('users')->signinUrl($package);
    $package->redirect($signin);
    return;
}

$search = $cms->factory()->search();
$search->where('${dso.type} = "proposal" AND ${owner} = :user');
$search->order('${dso.created.date} desc');
$props = $search->execute(['user'=>$user->id()]);

if (!$props) {
    $cms->helper('notifications')->printNotice('No proposals found for user <code>'.$user->identifier().'</code>');
    return;
}

echo "<table width='100%'>";
echo "<tr><th>Proposal</th><th>Submitted</th><th>Funding decision</th></tr>";
foreach ($props as $prop) {
    echo "<tr>";
    echo '<td>'.$prop->link().'</td>';
    echo '<td>'.($prop->window()?$prop->window()->link():'[unknown]').'</td>';
    echo '<td>'.($prop->finalDecision()?$prop->finalDecision()->fundedHR():'[no decision]').'</td>';
    echo "</tr>";
}
echo '</table>';
