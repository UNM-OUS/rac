<?php
$group = $package->noun();

printCalls($group->openCalls(), 'Current calls for proposals');
printCalls($group->upcomingCalls(), 'Upcoming calls for proposals');
printCalls($group->pastCalls(), 'Past calls for proposals');

function printCalls($calls,$title) {
    if (!$calls) {
        return;
    }
    echo "<h2>$title</h2>";
    echo "<ul>";
    foreach ($calls as $call) {
        echo "<li>".$call->link()."</li>";
    }
    echo "</ul>";
}