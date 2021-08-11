<?php
$prop = $package->noun()->prop();
$ratings = $cms->helper('racratings')->ratings($prop);
if ($ratings) {
    echo '<h2>Scores from reviewers</h2>';
}
foreach ($ratings as $rating) {
    echo $rating->body();
}