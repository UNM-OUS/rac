<?php
$package->cache_noStore();

echo "<p>During bulk assignment proposals will only be assigned to members who are marked as being of the same discipline as the proposal. Regular reviewers from the same discipline are also preferred, but in that case other disciplines will be used if not enough reviewers of the same discipline are available.</p>";

$members = $cms->helper('rac')->members();

$form = new \Formward\Form('Set reviewer disciplines');

foreach ($members as $member) {
    $form[$member->netid()] = new \Formward\Fields\CheckboxList($member->name());
    $form[$member->netid()]->options($cms->helper('rac')::DISCIPLINES);
    foreach ($member->disciplines() as $discipline) {
        if ($form[$member->netid()][md5($discipline)]) {
            $form[$member->netid()][md5($discipline)]->default(true);
        }
    }
}

if ($form->handle()) {
    foreach ($members as $member) {
        if ($form[$member->netid()]->value() != $member->disciplines()) {
            $member->disciplines($form[$member->netid()]->value());
        }
    }
}

echo $form;
