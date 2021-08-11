<?php
$package->cache_noStore();
$rac = $cms->helper('rac');

$name = $package['url.args.name'];
$existing = $rac->mailTemplate($name);

$form = $cms->helper('forms')->form(($existing?'Edit':'Add').' template: '.$name);

$form['subject'] = $cms->helper('forms')->field('text', 'Subject');
$form['subject']->required('true');

$form['fromchair'] = $cms->helper('forms')->field('checkbox', 'Send as current chair instead of as committee');

$form['body'] = $cms->helper('forms')->field('digraph_content_default', 'Body');
$form['body']->required('true');
$form['body']->extra([]);

$form->default($existing);

echo $form;

if ($form->handle()) {
    $rac->addMailTemplate(
        $name,
        $form['subject']->value(),
        $form['body']->value(),
        $form['fromchair']->value()
    );
    $cms->helper('notifications')->flashConfirmation('Email template updated');
    $package->redirect(
        $this->url('_rac_chair', 'mail_templates')
    );
}