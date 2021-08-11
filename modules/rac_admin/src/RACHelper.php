<?php
namespace Digraph\Modules\rac_admin;

use Digraph\Helpers\AbstractHelper;
use Digraph\Modules\rac_proposals\ProposalCall;
use Digraph\Modules\rac_proposals\Proposal;

class RACHelper extends AbstractHelper
{
    protected $members;
    protected $mailTemplates;
    protected $workingProps;
    protected $workingCalls;
    const DISCIPLINES = [
        "Physical Sciences" => "Physical Sciences — e.g. chemistry, earth and planetary, mathematics and statistics, physics and astronomy.",
        "Life Sciences" => "Life Sciences — e.g. biology, psychology.",
        "Social Sciences" => "Social Sciences — e.g. anthropology, business and administrative sciences, economics, geography, history, law, political science, sociology.",
        "Engineering" => "Engineering — all departments of the School of Engineering.",
        "Education" => "Education — all departments of the College of Education.",
        "Humanities" => "Humanities — e.g. architecture, English, journalism, foreign languages and literatures, Spanish and Portuguese, philosophy, communication.",
        "Fine Arts" => "Fine Arts — all departments of the College of Fine Arts.",
    ];

    public function datastore()
    {
        return $this->cms->helper('datastore')->namespace('rac_general');
    }

    public function mailTemplates(array $set = null)
    {
        if ($set) {
            $this->datastore()->set('mailTemplates', $set);
            $this->mailTemplates = null;
        }
        if ($this->mailTemplates === null) {
            $this->mailTemplates = $this->datastore()->get('mailTemplates');
        }
        return $this->mailTemplates;
    }

    public function addMailTemplate($name, $subject, $body, $fromChair=false)
    {
        $templates = $this->mailTemplates();
        $templates[$name] = [
            'subject' => $subject,
            'body' => $body,
            'fromchair' => $fromChair
        ];
        $this->mailTemplates($templates);
    }

    public function mailTemplate($name)
    {
        return @$this->mailTemplates()[$name];
    }

    public function sendMailTemplate($prop, $template)
    {
        if (!($template = $this->mailTemplate($template))) {
            return false;
        }
        $this->sendMail(
            $prop,
            $template['subject'],
            $this->cms->helper('filters')->filterContentField($template['body'], $prop['dso.id']),
            $template['fromchair']
        );
        return true;
    }

    public function sendMail($to_or_prop, $subject, $body_html, $fromChair=false)
    {
        if ($to_or_prop instanceof Proposal) {
            $subject .= ' [Proposal #'.$to_or_prop['dso.id'].']';
        }
        $subject = $this->emailCodes($subject, $to_or_prop);
        $body_html = $this->emailCodes($body_html, $to_or_prop);
        //prepare message for queuing
        $message = $this->cms->helper('mail')->message();
        $message->addTag('rac');
        //set subject
        $message->setSubject($subject);
        //set to address (either address directly, or submitter from Proposal)
        if ($to_or_prop instanceof Proposal) {
            $message->addTo([$to_or_prop['submitter.email'], $to_or_prop['submitter.firstname'].' '.$to_or_prop['submitter.lastname']]);
            $message->addTag($to_or_prop['dso.id']);
            if ($decision = $to_or_prop->finalDecision()) {
                $message->addTag($decision['dso.id']);
            }
            if ($window = $to_or_prop->window()) {
                $message->addTag($window['dso.id']);
            }
        } else {
            $message->addTo($to_or_prop);
        }
        //set body
        $message->setBody($body_html);
        //set from/ccs/bccs
        $message->addCC(['fsrac@unm.edu','Research Allocations Committee']);
        if ($fromChair) {
            if ($member = $this->chair()) {
                $message->setFrom([$member->email(), 'RAC Chair '.$member->name()]);
                $message->addCC([$member->email(), $member->name()]);
            } else {
                $message->setFrom(['fsrac@unm.edu','RAC Chair']);
            }
        } else {
            $message->setFrom(['fsrac@unm.edu','Research Allocations Committee']);
        }
        //set debug bcc
        foreach ($this->cms->config['rac.email_debug_bcc'] as $bcc) {
            $message->addBCC($bcc);
        }
        //attempt to send and return result
        $this->cms->helper('mail')->send($message);
        return true;
    }

    protected function emailCodes($text, $prop)
    {
        $s = $this->cms->helper('strings');
        if ($prop instanceof Proposal) {
            $codes = [
                "[submitter_fname]" => $prop['submitter.firstname'],
                "[submitter_lname]" => $prop['submitter.lastname'],
                "[submitter_email]" => $prop['submitter.email'],
                "[proposal_title]" => $prop['submission.title'],
                "[proposal_requested]" => $prop->requestedHR(),
                "[proposal_funded]" => '[no decision]',
                "[proposal_id]" => $prop['dso.id'],
                "[proposal_report_due]" => $prop['report_due']?$s->datetime($prop['report_due']):'[no due date assigned]',
                "[url_proposal]" => "".$prop->url()->string(),
                "[url_decision]" => $prop->url()->string(),
                "[url_submitfinal]" => "".$prop->url('submit-final-report'),
                "[url_guidelines]" => $this->cms->helper('urls')->url('guidelines'),
                "[url_recipientguide]" => $this->cms->helper('urls')->url('recipient-guide')
            ];
        } else {
            $codes = [];
        }
        if ($decision = $prop->finalDecision()) {
            $codes['[proposal_funded]'] = $decision->fundedHR();
            $codes['[url_decision]'] = $decision->url()->string();
        }
        if ($window = $prop->window()) {
            $codes['[call_semester]'] = $prop->window()['semester.semester'];
            $codes['[call_year]'] = $prop->window()['semester.year'];
            $codes['[call_end]'] = $prop->window()->endHR();
            $codes['[url_guidelines]'] = $window->url('version-jumper', ['page'=>'prop/guidelines']);
            $codes['[url_recipientguide]'] = $window->url('version-jumper', ['page'=>'prop/recipient-guide']);
        }
        if ($chair = $this->chair()) {
            $codes['[chair_name]'] = $chair->name();
            $codes['[chair_email]'] = $chair->email();
        } else {
            $codes['[chair_name]'] = 'RAC Chair';
            $codes['[chair_email]'] = 'fsrac@unm.edu';
        }
        foreach ($codes as $code => $rep) {
            $text = str_replace($code, $rep, $text);
        }
        return $text;
    }

    public function windowDatastore(ProposalCall $call)
    {
        return $this->cms->helper('datastore')->namespace('rac_window_'.$call['dso.id']);
    }

    public function userAssignments(ProposalCall $call, string $netid=null)
    {
        if (!$netid) {
            $netid = $this->cms->helper('users')->user()->identifier();
        }
        $props = [
            'lead' => [],
            'regular' => []
        ];
        foreach ($this->assignments($call) as $pid => $types) {
            foreach ($types as $type => $ns) {
                if (in_array($netid, $ns)) {
                    $props[$type][] = $this->cms->read($pid);
                }
            }
        }
        return $props;
    }

    public function lockedAssignments(ProposalCall $call)
    {
        return !!$this->windowDatastore($call)->get('assignments_locked');
    }

    public function lockAssignments(ProposalCall $call)
    {
        $this->windowDatastore($call)->set('assignments_locked', true);
    }

    public function unlockAssignments(ProposalCall $call)
    {
        $this->windowDatastore($call)->set('assignments_locked', false);
    }

    public function assignments(ProposalCall $call)
    {
        $out = $this->windowDatastore($call)->get('assignments');
        return $out?$out:[];
    }

    public function memberAssignments(ProposalCall $call, string $netid)
    {
        $out = [];
        foreach ($this->assignments($call) as $pid => $types) {
            foreach ($types as $type => $ns) {
                if (in_array($netid, $ns)) {
                    $out[$pid] = $type;
                }
            }
        }
        return $out;
    }

    public function chair()
    {
        foreach ($this->members() as $member) {
            foreach ($member->positions() as $pos) {
                if ($pos == 'chair') {
                    return $member;
                }
            }
        }
        return null;
    }

    public function member(string $netid)
    {
        foreach ($this->members() as $member) {
            if ($member->netid() == $netid) {
                return $member;
            }
        }
        return null;
    }

    public function members()
    {
        if ($this->members === null) {
            $members = $this->cms->helper('users')->groupSource('rac')->members();
            $members = array_map(
                function ($data) {
                    if ($data['netid']) {
                        return new RACMember($data, $this->cms);
                    } else {
                        return false;
                    }
                },
                $members
            );
            $this->members = array_filter($members);
        }
        return $this->members;
    }
    
    public function workingCalls()
    {
        if ($this->workingCalls === null) {
            list($semester, $year) = explode(' ', $this->workingSemesterName());
            $search = $this->cms->factory()->search();
            $search->where('${dso.type} = "proposal-call" AND ${semester.year} = :year AND ${semester.semester} = :semester');
            $search->order('${requestamount.maximum} asc, ${requestamount.minimum} asc');
            $this->workingCalls = $search->execute([
                'year' => $year,
                'semester' => $semester
            ]);
            foreach ($this->workingCalls as $call) {
                if ($call->open()) {
                    if ($call->endHR()) {
                        $this->cms->helper('notifications')->warning($call->link().' is still open. Submissions can be made until '.$call->endHR());
                    } else {
                        $this->cms->helper('notifications')->warning($call->link().' is still open. It has no end date.');
                    }
                }
            }
        }
        return $this->workingCalls;
    }

    public function propStatsTable(array $props, bool $includeDraftDecisions = false)
    {
        $stub = [
            'completed' => [0,0],
            'no decision' => [0,0],
            'funded' => [0,0],
            'denied' => [0,0],
            'incomplete' => [0,0],
        ];
        $data = [
            'Total' => $stub
        ];
        foreach ($props as $prop) {
            $window = $prop->window()->name();
            //stub window name
            if (!isset($data[$window])) {
                $data[$window] = $stub;
            }
            //complete/incomplete props
            if ($prop->complete()) {
                $data['Total']['completed'][0]++;
                $data['Total']['completed'][1] += intval($prop['submission.requested']);
                $data[$window]['completed'][0]++;
                $data[$window]['completed'][1] += intval($prop['submission.requested']);
            } else {
                $data['Total']['incomplete'][0]++;
                $data['Total']['incomplete'][1] += intval($prop['submission.requested']);
                $data[$window]['incomplete'][0]++;
                $data[$window]['incomplete'][1] += intval($prop['submission.requested']);
            }
            //props with decisions
            $decision = $includeDraftDecisions ? $prop->decision() : $prop->finalDecision();
            if ($decision) {
                if ($decision->funded()) {
                    //props funded
                    $data['Total']['funded'][0]++;
                    $data['Total']['funded'][1] += intval($decision->funded());
                    $data[$window]['funded'][0]++;
                    $data[$window]['funded'][1] += intval($decision->funded());
                } else {
                    //props not funded
                    $data['Total']['denied'][0]++;
                    $data['Total']['denied'][1] += intval($prop['submission.requested']);
                    $data[$window]['denied'][0]++;
                    $data[$window]['denied'][1] += intval($prop['submission.requested']);
                }
            } else {
                //props with no decisions
                $data['Total']['no decision'][0]++;
                $data['Total']['no decision'][1] += intval($prop['submission.requested']);
                $data[$window]['no decision'][0]++;
                $data[$window]['no decision'][1] += intval($prop['submission.requested']);
            }
        }

        //remove "Total" if there's only one window
        if (count($data) == 2) {
            unset($data['Total']);
        }

        //convert data into a table
        ob_start();
        echo "<table>";
        echo "<tr><th colspan=2 style='visibility:hidden;'>&nbsp;</th><th>".implode('</th><th>', array_keys($data))."</th></tr>";
        echo "<tr style='display:none;'></tr>";

        //completed
        if (reset($data)['completed'][0]) {
            echo "<tr><td rowspan=2>Completed</td><td>Proposals</td>";
            foreach ($data as $seg) {
                echo "<td>".number_format($seg['completed'][0])."</td>";
            }
            echo "</tr><td>Amount</td>";
            foreach ($data as $seg) {
                echo "<td>$".number_format($seg['completed'][1])."</td>";
            }
            echo "</tr>";
        }

        //funded
        if (reset($data)['funded'][0]) {
            echo "<tr><td rowspan=2>Funded</td><td>Proposals</td>";
            foreach ($data as $seg) {
                echo "<td>".number_format($seg['funded'][0])."</td>";
            }
            echo "</tr><td>Amount</td>";
            foreach ($data as $seg) {
                echo "<td>$".number_format($seg['funded'][1])."</td>";
            }
            echo "</tr>";
        }

        //denied
        if (reset($data)['denied'][0]) {
            echo "<tr><td rowspan=2>Not funded</td><td>Proposals</td>";
            foreach ($data as $seg) {
                echo "<td>".number_format($seg['denied'][0])."</td>";
            }
            echo "</tr><td>Amount</td>";
            foreach ($data as $seg) {
                echo "<td>$".number_format($seg['denied'][1])."</td>";
            }
            echo "</tr>";
        }

        //incomplete
        if (reset($data)['incomplete'][0]) {
            echo "<tr><td rowspan=2>Incomplete</td><td>Proposals</td>";
            foreach ($data as $seg) {
                echo "<td>".number_format($seg['incomplete'][0])."</td>";
            }
            echo "</tr><td>Amount</td>";
            foreach ($data as $seg) {
                echo "<td>$".number_format($seg['incomplete'][1])."</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
        return ob_get_clean();
    }

    public function workingProps()
    {
        if ($this->workingProps === null) {
            $this->workingProps = [];
            foreach ($this->workingCalls()??[] as $call) {
                $this->workingProps = array_merge($this->workingProps, $call->allSubmissions());
            }
        }
        return $this->workingProps;
    }

    public function workingSemesterName()
    {
        $session = $this->cms->helper('session');
        if (!($semester = $session->get('rac-semester-working'))) {
            $this->semesterForm();
        }
        if (!($semester = $session->get('rac-semester-working'))) {
            throw new \Exception("Failed to get working semester from session and form", 1);
        }
        return $semester;
    }

    public function propWindowForm()
    {
        $session = $this->cms->helper('session');

        $form = $this->cms->helper('forms')->form();
        $form->name('rac_prop_selector');
        $form['call'] = $this->cms->helper('forms')->field('select', '');
        $form['call']->required(true);
        $form->addClass('compact-form');
        $form->addClass('autosubmit');

        //locate calls
        $group = $this->cms->read('prop');

        //put calls into form and pick default
        $default = null;
        $options = [];
        foreach ($group->upcomingCalls() as $call) {
            $options[$call['dso.id']] = 'Upcoming: '.$call->name();
        }
        foreach ($group->openCalls() as $call) {
            $options[$call['dso.id']] = 'Open: '.$call->name();
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

        //get/save value in session
        if ($form->handle()) {
            $session->set('rac-propwindow-working', $form['call']->value());
        }
        if ($session->get('rac-propwindow-working')) {
            $form['call']->default($session->get('rac-propwindow-working'));
        }

        //submit button
        $form->submitButton()->label('Select proposal window');

        //return form
        return $form;
    }

    public function semesterForm()
    {
        $session = $this->cms->helper('session');

        $form = $this->cms->helper('forms')->form();
        $form->name('rac_prop_selector');
        $form['sem'] = $this->cms->helper('forms')->field('select', '');
        $form['sem']->required(true);
        $form->addClass('compact-form');
        $form->addClass('autosubmit');

        //locate calls
        $group = $this->cms->read('prop');

        //put calls into form and pick default
        $default = null;
        $options = [];
        foreach ($group->upcomingCalls() as $call) {
            $options[$call['semester.semester'].' '.$call['semester.year']] = $call['semester.semester'].' '.$call['semester.year'];
        }
        foreach ($group->openCalls() as $call) {
            $options[$call['semester.semester'].' '.$call['semester.year']] = $call['semester.semester'].' '.$call['semester.year'];
            if (!$default) {
                $default = $call['semester.semester'].' '.$call['semester.year'];
            }
        }
        foreach ($group->pastCalls() as $call) {
            $options[$call['semester.semester'].' '.$call['semester.year']] = $call['semester.semester'].' '.$call['semester.year'];
            if (!$default) {
                $default = $call['semester.semester'].' '.$call['semester.year'];
            }
        }
        $form['sem']->options($options);
        $form['sem']->default($default);

        //get/save value in session
        if ($form->handle()) {
            $session->set('rac-semester-working', $form['sem']->value());
        }
        if ($session->get('rac-semester-working')) {
            $form['sem']->default($session->get('rac-semester-working'));
        } else {
            $session->set('rac-semester-working', $form['sem']->value());
        }

        //submit button
        $form->submitButton()->label('Select working semester');

        //return form
        return $form;
    }
}
