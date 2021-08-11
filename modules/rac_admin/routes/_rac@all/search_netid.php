<?php
$package->cache_noStore();

$form = $cms->helper('forms')->form('');
$form['netid'] = $cms->helper('forms')->field('netid', 'Submitter NetID');
$form['netid']->default(@$_GET['netid']);

echo $form;

$netid = $_GET['netid'] ?? null;
if ($form->handle()) {
    $netid = $form['netid']->value();
}

if ($netid) {
    echo "<h2>Search results</h2>";
    $search = $cms->factory()->search();
    $search->where('${dso.type} = "proposal" AND ${owner} = :netid');
    $search->order('${dso.created.date} desc');
    $results = $search->execute(['netid'=>$netid.'@netid']);
    if (!$results) {
        echo "<p>No results</p>";
        return;
    }
    echo "<table>";
    echo "<tr><th>Proposal</th><th>Call</th><th>Status</th></tr>";
    foreach ($results as $res) {
        echo "<tr>";
        echo "<td valign='top'>".$res->infoCell()."</td>";
        echo "<td valign='top'>".$res->window()->link()."</td>";
        echo "<td valign='top'>";
        if ($res->complete()) {
            if ($decision = $res->finalDecision()) {
                echo $decision->info();
            } else {
                echo "[no decision]";
            }
        } else {
            echo '[incomplete]';
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
