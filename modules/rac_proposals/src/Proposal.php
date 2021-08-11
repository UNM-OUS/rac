<?php
namespace Digraph\Modules\rac_proposals;

class Proposal extends \Digraph\Modules\Submissions\Submission
{
    const SLUG_ID_LENGTH = 4;
    const HOOK_TRIGGER_PARENTS = false;
    const HOOK_TRIGGER_CHILDREN = false;

    protected $_decision = null;
    protected $_report = null;

    public function hook_Added()
    {
        $this->factory->cms()->helper('rac')->sendMailTemplate($this, 'prop_started');
    }

    public function infoCell()
    {
        $chunks = $this->parts()->chunks();
        $out = '<strong>' . $this->link() . '</strong><br>';
        ob_start();
        echo "<dl>";
        echo "<dt>Amount Requested</dt><dd>" . $this->requestedHR() . "</dd>";
        echo "<dt>Discipline</dt><dd>" . $this['submission.discipline'] . "</dd>";
        echo "<dt>School/College</dt><dd>" . $this['submitter.college'] . "</dd>";
        echo "<dt>Department</dt><dd>" . $this['submitter.department'] . "</dd>";
        echo "</dl>";
        $out .= ob_get_contents();
        ob_end_clean();
        $url = $this->factory->cms()->helper('urls')->url(
            '_rac',
            'search_netid',
            [
                'netid' => preg_replace('/@netid$/', '', $this['owner']),
            ]
        );
        if ($report = $this->report()) {
            $out .= "<a href='" . $report->url() . "'>View final report</a><br>";
        } elseif ($due = $this['report_due']) {
            if (time() > $due) {
                $s = $this->factory->cms()->helper('strings');
                $out .= "<strong>Overdue final report: " . $s->date($due) . "</strong><br>";
            }
        }
        if ($this->factory->cms()->helper('permissions')->checkUrl($url)) {
            $out .= '<a href="' . $url . '">All proposals by user</a><br>';
        }
        $out .= "<a href='" . $this->url('download') . "'>Download proposal</a>";
        return $out;
    }

    public function finalizeDecision()
    {
        $this['decision_finalized'] = true;
        $this->update();
    }

    public function finalReportClass()
    {
        if ($this->report()) {
            return $this->report()->reportType();
        }
        return 'proposal-report-2019';
    }

    public function finalDecision()
    {
        if ($this['decision_finalized']) {
            return $this->decision();
        }
        return null;
    }

    public function decision()
    {
        if ($this->_decision === null) {
            $decisions = $this->factory->cms()
                ->helper('graph')
                ->children($this['dso.id'], 'proposal-decision', 1, '${dso.created.date} desc');
            $this->_decision = $decisions ? array_shift($decisions) : false;
        }
        return $this->_decision;
    }

    public function isFunded()
    {
        if ($d = $this->finalDecision()) {
            return !!$d->funded();
        }
        return false;
    }

    public function report()
    {
        if ($this->_report === null) {
            $reports = $this->factory->cms()
                ->helper('graph')
                ->children($this['dso.id'], 'proposal-report', 1, '${dso.created.date} desc');
            $this->_report = $reports ? array_shift($reports) : false;
        }
        return $this->_report;
    }

    public function defaultPartsClass()
    {
        return Parts\ProposalParts2019::class;
    }

    public function defaultSubmitterFieldClass()
    {
        return Fields\SubmitterField2019::class;
    }

    public function defaultSubmissionFieldClass()
    {
        return Fields\SubmissionField2019::class;
    }

    public function addressHR()
    {
        return $this->cms()->helper('filters')
            ->filterPreset(
                $this['submitter.address'],
                'text-safe'
            );
    }

    public function requestedHR()
    {
        return '$' . number_format($this['submission.requested']);
    }

    public function name($verb = null)
    {
        return $this['submitter.lastname'] . ': ' . $this['submission.title'];
    }

    public function isViewable()
    {
        return
        parent::isViewable() || //parent isViewable can allow
        $this->cms()->helper('permissions')->check('proposal/view', 'rac'); //proposal/view permissions
    }

    public function isEditable()
    {
        return
        (parent::isEditable() && !$this->finalDecision()) || //parent isEditable can allow, but only if no decision is posted
        $this->cms()->helper('permissions')->check('proposal/edit', 'rac'); //proposal/edit permissions
    }

    public function hook_postEditUrl()
    {
        return $this->url('display', null)->string();
    }

    public function hook_postAddUrl()
    {
        return $this->url('display', null)->string();
    }

    public function searchIndexed()
    {
        return false;
    }

    public function slugVars()
    {
        return [
            'lname' => $this['submitter.lastname'],
        ];
    }

    public function formMap(string $action): array
    {
        $map = parent::formMap($action);

        $min = 0;
        $max = 10000;
        if ($action == 'add') {
            if ($parent = $this->cms()->package()->noun()) {
                $min = intval($parent['requestamount.minimum']);
                $max = intval($parent['requestamount.maximum']);
            }
        }
        $map['submission']['call'] = [
            'setMinimum' => [$min],
            'setMaximum' => [$max],
        ];

        return $map;
    }
}
