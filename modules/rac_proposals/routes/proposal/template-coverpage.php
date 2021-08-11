<?php
$package->cache_noStore();

if (!$package->noun()->isViewable()) {
    //deny access for those with no access
    $package->error(403);
    return;
}

$package['fields.page_title'] = "RAC Cover Page";
$package['response.template'] = 'pdf/blank-pdf.twig';
$package['response.outputfilter'] = 'pdf';
$package['fields.page_name'] = "RAC Cover Page";

$submission = $package->noun();
$parts = $submission->parts();
$chunks = $parts->chunks();

echo $cms->helper('templates')->render(
    'rac/coverpage.twig',
    [
        'prop' => $submission
    ]
);
