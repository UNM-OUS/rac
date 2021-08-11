<?php
namespace Digraph\Modules\rac_admin\Ratings;

use Digraph\Modules\rac_admin\RACMember;
use Digraph\Modules\rac_proposals\Proposal;

abstract class AbstractRating implements RatingInterface
{
    protected static $reviewerIDs = [];
    protected $prop;
    protected $datastore;

    public function __construct(Proposal $prop, string $netid)
    {
        $this->netid = $netid;
        $this->prop = $prop;
        $this->dsname = $netid;
        $this->datastore = $prop->cms()->helper('racratings')->propDatastore($prop);
        $this->data = $this->datastore->get($this->dsname);
        if (!isset(static::$reviewerIDs[$netid])) {
            static::$reviewerIDs[$netid] = count(static::$reviewerIDs) + 1;
        }
    }

    public function notesHR(): string
    {
        return $this->prop->cms()->helper('filters')
            ->filterContentField($this->data()['comments'], $this->prop['dso.id']);
    }

    public function netid(): string
    {
        return $this->netid;
    }

    public function member(): ?RACMember
    {
        return $this->prop->cms()->helper('rac')->member($this->netid);
    }

    public function scoreHR():string
    {
        return "Score: ".$this->quantScore();
    }

    /**
     * Return the score of this rating based on any quantifiable rating questions.
     * Must be an integer, normalized on a scale of 0-100
     *
     * @return ?int
     */
    public function quantScore(): ?int
    {
        if (!$this->data()) {
            return null;
        }
        $sum = 0;
        $count = 0;
        foreach ($this->data()['ratings'] as $rating) {
            if ($rating > 0) {
                $sum += intval($rating) - 1;
                $count++;
            }
        }
        if (!$count) {
            return null;
        }
        return intval(100 * ($sum / $count) / 4);
    }

    public function fundable(): bool
    {
        return @$this->data()['fundable'] == 'yes';
    }

    public function meta()
    {
        return @$this->data['meta'];
    }

    protected function save()
    {
        $this->datastore->set($this->dsname, $this->data);
    }

    public function data($set = null)
    {
        if ($set !== null) {
            $this->data['data'] = $set;
            $this->updateMetaData();
            $this->save();
        }
        return @$this->data['data'];
    }

    protected function updateMetaData()
    {
        if (!$this->data['meta']) {
            $this->data['meta'] = [
                'created' => [
                    'user' => $this->prop->cms()->helper('users')->id(),
                    'time' => time(),
                ],
                'log' => [],
            ];
        } else {
            $this->data['log'][time()] = $this->prop->cms()->helper('users')->id();
        }
    }
}
