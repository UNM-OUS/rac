<?php
namespace Digraph\Modules\rac_proposals;

class ProposalGroup extends \Digraph\Modules\CoreTypes\Page
{
    protected $_calls = null;

    public function upcomingCalls()
    {
        return array_filter(
            $this->calls(),
            function ($e) {
                return $e->start() && $e->start() > time();
            }
        );
    }

    public function pastCalls()
    {
        return array_filter(
            $this->calls(),
            function ($e) {
                return $e->end() && $e->end() < time();
            }
        );
    }

    public function openCalls()
    {
        return array_filter(
            $this->calls(),
            function ($e) {
                return $e->open();
            }
        );
    }

    public function calls()
    {
        if ($this->_calls === null) {
            $this->_calls = $this->factory->cms()
                ->helper('graph')
                ->children(
                    $this['dso.id'],
                    'proposal-call',
                    1,
                    '${window.end} desc, ${window.start} asc, ${dso.created.date} desc'
                );
        }
        return $this->_calls;
    }
}
