<?php
$package->cache_noStore();
$form = $cms->helper('forms')->form('');
$session = $cms->helper('session');

//put calls into form and pick default
$form['call'] = $cms->helper('forms')->field('select', 'Call for proposals');
$form['call']->required(true);
$group = $cms->read('prop');
$default = null;
$options = [];
foreach ($group->upcomingCalls() as $call) {
    $options[$call['dso.id']] = 'Upcoming: ' . $call->name();
}
foreach ($group->openCalls() as $call) {
    $options[$call['dso.id']] = 'Open: ' . $call->name();
    if (!$default) {
        $default = $call['dso.id'];
    }
}
foreach ($group->pastCalls() as $call) {
    $options[$call['dso.id']] = $call->name();
    if (!$default) {
        $default = $call['dso.id'];
    }
}
$form['call']->options($options);
$form['call']->default($default);

// recipient rules
$form['recipients'] = $cms->helper('forms')->field('select', 'Recipients');
$form['recipients']->options([
    null => '-- please select --',
    'prop_awarded' => 'Funding awarded',
    'prop_denied' => 'Funding denied',
    'prop_complete' => 'All completed submissions',
    'prop_incomplete' => 'All incomplete submissions',
    'prop_report_due' => 'Final report due date assigned, not submitted',
    'prop_report_due_soon' => 'Final report due date in next 30 days, not submitted',
    'prop_report_overdue' => 'Final report overdue',
    'prop_report_submitted' => 'Final report submitted',
]);
$form['recipients']->required(true);
$form['recipients']->addClass('select_recipients');

// field for setting template name
$form['template'] = $cms->helper('forms')->field('select', 'Message template');
$options = $cms->config['rac.email_named_templates'];
foreach ($cms->helper('rac')->mailTemplates() ?? [] as $name => $template) {
    if (!isset($options[$name])) {
        $options[$name] = $name . ': ' . $template['subject'];
    }
}
$form['template']->options([null => '-- please select --'] + $options);
$form['template']->required(true);
$form['template']->addClass('select_template');

// build redirection to sender page
if ($form->handle()) {
    $filters = [
        'prop_awarded' => function ($e) {return $e->isFunded();},
        'prop_denied' => function ($e) {return !$e->isFunded();},
        'prop_complete' => function ($e) {return $e->complete();},
        'prop_incomplete' => function ($e) {return !$e->complete();},
        'prop_report_due' => function ($e) {return $e['report_due'] && !$e->report();},
        'prop_report_due_soon' => function ($e) {return time() > $e['report_due'] - (86400 * 30) && !$e->report();},
        'prop_report_overdue' => function ($e) {return $e['report_due'] && time() > $e['report_due'] && !$e->report();},
        'prop_report_submitted' => function ($e) {return !!$e->report();},
    ];
    $filter = @$filters[$form['recipients']->value()] ?? function ($e) {return false;};
    $recipients = $cms->read($form['call']->value())->allSubmissions();
    $recipients = array_filter($recipients, $filter);
    $recipients = array_map(function ($e) {return $e['dso.id'];}, $recipients);
    if (count($recipients)) {
        $url = $cms->helper('session')->persistData(
            [
                'template' => $form['template']->value(),
                'recipients' => $recipients,
                'after' => $package->url()->__toString(),
            ],
            '_rac',
            'mail_send'
        );
        $package->redirect($url);
    } else {
        $cms->helper('notifications')->printError('No matching proposals found');
    }
}

// print form
echo $form;
?>
<script>
$(()=>{
    var $recipients = $('select.select_recipients');
    var $template = $('select.select_template');
    $recipients.change(function(e){
        $template.val($recipients.val());
    });
});
</script>
