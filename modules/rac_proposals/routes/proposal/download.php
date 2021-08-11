<?php
$package->cache_noCache();

//set up package/permissions
$prop = $package->noun();
if (!$prop->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}
$package['fields.page_title'] = "RAC Proposal Download";
$package['fields.page_name'] = "RAC Proposal Download";

//set up zip file
$ZIP_FILENAME = $cms->config['paths.cache'].'/rac_zip_'.$prop['dso.id'].'.zip';

if (!is_file($ZIP_FILENAME) || $prop['dso.modified.date'] > filemtime($ZIP_FILENAME)) {
    $ZIP = new \ZipArchive();
    $ZIP->open($ZIP_FILENAME, \ZipArchive::CREATE);

    //add files using zip() method in parts
    $prop->parts()->zip($ZIP);

    //close zip file
    $ZIP->close();
}

// //add to package
$FILENAME = $prop['submitter.lastname'].' - '.substr($prop['submission.title'], 0, 20).' - '.$prop['dso.id'].'_'.$prop['dso.modified.date'].'.zip';
$package->makeMediaFile($FILENAME);
$package['response.outputmode'] = 'readfile';
$package['response.readfile'] = $ZIP_FILENAME;
