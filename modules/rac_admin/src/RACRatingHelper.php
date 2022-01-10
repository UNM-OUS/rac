<?php

namespace Digraph\Modules\rac_admin;

use Digraph\Helpers\AbstractHelper;
use Digraph\Modules\rac_admin\Ratings\Rating2019;
use Digraph\Modules\rac_admin\Ratings\Rating2020;
use Digraph\Modules\rac_admin\Ratings\Rating2020Fall;
use Digraph\Modules\rac_proposals\Proposal;

class RACRatingHelper extends AbstractHelper
{
    public function rating(Proposal $prop, string $netid)
    {
        if ($window = $prop->window()) {
            if ($window['semester.year'] < 2020) {
                return new Rating2019($prop, $netid);
            } elseif ($window['semester.year'] == 2020 && $window['semester.semester'] == 'Spring') {
                return new Rating2020($prop, $netid);
            }
        }
        return new Rating2020Fall($prop, $netid);
    }

    public function fundabilityScore(Proposal $prop)
    {
        $score = 0;
        $ratings = $this->ratings($prop);
        foreach ($ratings as $rating) {
            if ($rating->data()) {
                if ($rating->fundable()) {
                    $score += $rating->quantScore();
                } else {
                    $score += $rating->quantScore() / 10;
                }
            }
        }
        $score = round($score / count($ratings));
        if ($prop['submission_optouts.prop_resubmission'] === false) {
            $score += 5;
        }
        return $score;
    }

    public function ratings(Proposal $prop)
    {
        $ratings = [];
        foreach ($this->propDatastore($prop)->getAll() as $netid => $rating) {
            $ratings[] = $this->rating($prop, $netid);
        }
        return $ratings;
    }

    public function propDatastore(Proposal $prop)
    {
        return $this->cms->helper('datastore')->namespace('rac_ratings_' . $prop['dso.id']);
    }
}
