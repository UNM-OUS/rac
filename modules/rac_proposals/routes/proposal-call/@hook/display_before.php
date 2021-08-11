<?php
$package->cache_noStore();

$call = $package->noun();
echo "<ul>";
echo "<li><a href='".$call->url('version-jumper',['page'=>'prop/guidelines'])."'>Proposal guidelines</a></li>";
if ($call->ended()) {
    echo "<li><a href='".$call->url('stats')."'>Proposal and funding statistics</a></li>";
    echo "<li><a href='".$call->url('funded')."'>Funded proposals</a></li>";
}
echo "</ul>";