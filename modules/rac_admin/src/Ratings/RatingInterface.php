<?php
namespace Digraph\Modules\rac_admin\Ratings;

use Digraph\Modules\rac_admin\RACMember;
use Digraph\Modules\rac_proposals\Proposal;

interface RatingInterface
{
    public function __construct(Proposal $prop, string $netid);

    /**
     * Get full body text of rating.
     *
     * @param boolean $showName
     * @return string
     */
    public function body($showName = false): string;

    /**
     * Get human-readable notes HTML.
     *
     * @return void
     */
    public function notesHR(): string;

    /**
     * Get the NetID of the rating author
     *
     * @return string
     */
    public function netid(): string;

    /**
     * Get full member object of the rating author
     *
     * @return RACMember|null
     */
    public function member(): ?RACMember;

    /**
     * Return the score of this rating based on any quantifiable rating questions.
     * Must be an integer, normalized on a scale of 0-100
     *
     * @return ?int
     */
    public function quantScore(): ?int;

    /**
     * Return whether this rating indicates the proposal is fundable.
     *
     * @return bool
     */
    public function fundable(): bool;

    public function meta();
    public function data($set = null);
    public function form();
}
