<?php
$package->cache_noCache();

$report = $package->noun();
$prop = $report->prop();
if ($prop && !$prop->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}

$fs = $cms->helper('filestore');
$files = $fs->allFiles($report);
if (!$files) {
    echo "no files";
    return;
}

foreach ($files as $f) {
    echo $f->metacard();
}
