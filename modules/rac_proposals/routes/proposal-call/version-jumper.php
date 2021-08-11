<?php
$target = $cms->read($package['url.args.page']);
if (!$target) {
    $package->error(404);
    return;
}

//for regular versioned pages, try to find a version that overlaps
if ($target instanceof \Digraph\Modules\CoreTypes\Versioned) {
    $call = $package->noun();
    $version = null;
    foreach (array_reverse($target->availableVersions()) as $v) {
        if ($call->end()) {
            if ($v->effectiveDate() < $call->end()) {
                $version = $v;
            }
        }
    }
    if ($version) {
        $package->redirect($version->url());
        return;
    }
}

//fall back to just redirecting to target main display
$package->redirect($target->url());
