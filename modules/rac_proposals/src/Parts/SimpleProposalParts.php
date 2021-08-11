<?php
namespace Digraph\Modules\rac_proposals\Parts;

use Digraph\Modules\rac_proposals\Parts\AbstractRACProposalParts;
use Digraph\Modules\rac_proposals\Parts\Chunks\UploadPDF;

/**
 * This Parts class is used for use as a single-upload fallback in case more
 * complex forms prove too difficult.
 */
class SimpleProposalParts extends AbstractRACProposalParts
{
    const PARTS_NAME = "RAC simple proposal form";
    const TEMPLATE_PREFIX = 'simple_';

    public function construct()
    {
        parent::construct();
        //mandatory PDF uploads
        $this->chunks['prop_proposal'] = new UploadPDF($this, 'prop_proposal', 'Proposal PDF');
    }

    /**
     * Add this proposal's parts to a Zip archive file
     *
     * @param \ZipArchive $zip
     * @return void
     */
    public function zip(\ZipArchive $zip)
    {
        $prop = $this->submission();
        $fs = $this->submission()->cms()->helper('filestore');
        //add metadata file
        $zip->addFromString(
            '_proposal info.txt',
            $this->submission()->cms()->helper('templates')->render(
                'rac/proposalinfo.txt.twig',
                [
                    'prop' => $prop,
                    'chunks' => $prop->parts()->chunks(),
                ]
            )
        );
        //proposal
        $this->zipAddFilesChunk($zip, 'prop_proposal', '');
    }
}
