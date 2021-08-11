<?php
$package->cache_noStore();
$form = $cms->helper('forms')->form('');
$session = $cms->helper('session');
$prop = $package->noun();

// field for setting template name
$form['template'] = $cms->helper('forms')->field('select', 'Message template');
$options = $cms->config['rac.email_named_templates'];
foreach ($cms->helper('rac')->mailTemplates() ?? [] as $name => $template) {
    if (!isset($options[$name])) {
        $options[$name] = $name . ': ' . $template['subject'];
    }
}

// remove obviously wrong templates
if (!$prop->complete()) {
    // proposal is incomplete
    unset($options['prop_awarded']);
    unset($options['prop_denied']);
    unset($options['prop_complete']);
    unset($options['prop_report_due']);
    unset($options['prop_report_due_soon']);
    unset($options['prop_report_overdue']);
    unset($options['prop_report_submitted']);
} else {
    // proposal is completed
    unset($options['prop_incomplete']);
}
if ($prop->isFunded()) {
    // proposal has been funded
    unset($options['prop_denied']);
} else {
    // proposal has not been funded
    unset($options['prop_awarded']);
    unset($options['prop_report_due']);
    unset($options['prop_report_due_soon']);
    unset($options['prop_report_overdue']);
    unset($options['prop_report_submitted']);
}
if (!$prop['report_due']) {
    // no due date assigned
    unset($options['prop_report_due']);
    unset($options['prop_report_due_soon']);
    unset($options['prop_report_overdue']);
    unset($options['prop_report_submitted']);
}
if (time() < $prop['report_due']) {
    // report is not overdue
    unset($options['prop_report_overdue']);
}
if ($prop->report()) {
    // report has been submitted
    unset($options['prop_report_due']);
    unset($options['prop_report_due_soon']);
    unset($options['prop_report_overdue']);
} else {
    // no report submitted
    unset($options['prop_report_submitted']);
}

// set options in field
$form['template']->options([null => '-- please select --'] + $options);
$form['template']->required(true);

echo $form;

// build redirection to sender page
if ($form->handle()) {
    $url = $cms->helper('session')->persistData(
        [
            'template' => $form['template']->value(),
            'recipients' => [$prop['dso.id']],
            'after' => $prop->url('messages')->__toString(),
        ],
        '_rac',
        'mail_send'
    );
    $package->redirect($url);
}
