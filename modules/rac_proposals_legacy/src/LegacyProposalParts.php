<?php
namespace Digraph\Modules\rac_proposals_legacy;

use Digraph\Modules\rac_proposals\Parts\AbstractRACProposalParts;
use Digraph\Modules\rac_proposals\Parts\Chunks\UploadPDFMulti;

/**
 * This Parts class is used for importing legacy data. It is designed to hold
 * the single PDFs that were used prior to 2019 when submitters just uploaded
 * their entire proposals as one giant PDF.
 */
class LegacyProposalParts extends AbstractRACProposalParts
{
    const PARTS_NAME = "RAC legacy proposal form";
    const TEMPLATE_PREFIX = 'legacy_';

    public function construct()
    {
        parent::construct();
        //mandatory PDF uploads
        $this->chunks['prop_proposal'] = new UploadPDFMulti($this, 'prop_proposal', 'Proposal file(s)');
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
