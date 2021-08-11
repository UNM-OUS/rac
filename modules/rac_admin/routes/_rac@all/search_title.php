<?php
$package->cache_noStore();

$form = $cms->helper('forms')->form('');
$form['query'] = $cms->helper('forms')->field('text', 'Query');
$form['query']->addTip('Search will only return results containing <em>all</em> terms entered above.');
$form['query']->addTip('Partial word matches are included (i.e. "state" would return results containing "statecraft").');

echo $form;

if ($form->handle()) {
    echo "<h2>Search results</h2>";
    $search = $cms->factory()->search();
    $args = [];
    $where = ['${dso.type} = "proposal"'];
    foreach (preg_split('/\s+/', strtolower($form['query']->value())) as $word) {
        $where[md5($word)] = '${submission.title} LIKE :'.md5($word);
        $args[md5($word)] = "%$word%";
    }
    $search->where(implode(' AND ', $where));
    $search->order('${dso.created.date} desc');
    $results = $search->execute($args);
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
