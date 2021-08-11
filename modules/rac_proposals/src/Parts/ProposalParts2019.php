<?php
namespace Digraph\Modules\rac_proposals\Parts;

class ProposalParts2019 extends AbstractRACProposalParts
{
    const PARTS_NAME = "RAC form 2019 revision";
    const TEMPLATE_PREFIX = '2019/';

    public function construct()
    {
        parent::construct();
        $t = $this->submission()->cms()->helper('templates');

        //abstract

        $this->chunks['prop_abstract'] = new Chunks\TextArea($this, 'prop_abstract', 'Proposal abstract');
        $this->chunks['prop_abstract']->instructions($this->instructions('prop_abstract'));
        $this->chunks['prop_abstract']->maxWords(250);

        //extended chunks

        // $this->chunks['prop_checklist'] = new Chunks\UploadPDF($this, 'prop_checklist', 'Checklist');
        // $this->chunks['prop_checklist']->maxPages(1);
        // $this->chunks['prop_checklist']->uploadFieldLabel('Upload completed checklist page');
        // $this->chunks['prop_checklist']->instructions($this->instructions('prop_checklist'));

        $this->chunks['prop_coverpage'] = new Chunks\UploadPDF($this, 'prop_coverpage', 'Cover page');
        $this->chunks['prop_coverpage']->maxPages(1);
        $this->chunks['prop_coverpage']->uploadFieldLabel('Upload cover page as a PDF');
        $this->chunks['prop_coverpage']->instructions($this->instructions('prop_coverpage'));

        $this->chunks['prop_resubmission'] = new Chunks\UploadPDF($this, 'prop_resubmission', 'Introduction to resubmission or revision application');
        $this->chunks['prop_resubmission']->optional('This is not a resubmitted or revised proposal');
        $this->chunks['prop_resubmission']->maxPages(1);
        $this->chunks['prop_resubmission']->uploadFieldLabel('Upload introduction as a PDF');
        $this->chunks['prop_resubmission']->instructions($this->instructions('prop_resubmission'));

        $this->chunks['prop_narrative'] = new Chunks\UploadPDFPlus($this, 'prop_narrative', 'Proposal narrative');
        $this->chunks['prop_narrative']->uploadFieldLabel('Upload narrative');
        $this->chunks['prop_narrative']->extrasLabel('Appendices');
        $this->chunks['prop_narrative']->maxPages(4);
        $this->chunks['prop_narrative']->instructions($this->instructions('prop_narrative'));

        $this->chunks['prop_extramural'] = new Chunks\UploadPDF($this, 'prop_extramural', 'Plan for transition to extramural sources of funding');
        $this->chunks['prop_extramural']->optional('This proposal does not require a transition to extramural funding');
        $this->chunks['prop_extramural']->maxPages(1);
        $this->chunks['prop_extramural']->instructions($this->instructions('prop_extramural'));

        // Maybe this isn't necessary because it can be rolled into the appendices portion of the narrative section
        // $this->chunks['prop_bibliography'] = new Chunks\UploadPDF($this, 'prop_bibliography', 'Bibliography/references');
        // $this->chunks['prop_bibliography']->maxPages(2);
        // $this->chunks['prop_bibliography']->instructions($this->instructions('prop_bibliography'));

        $this->chunks['prop_budget'] = new Chunks\UploadPDFPlus($this, 'prop_budget', 'Budget and justification');
        $this->chunks['prop_budget']->extrasLabel('Quotes, price lists, etc. as needed to justify budget');
        $this->chunks['prop_budget']->uploadFieldLabel('Upload budget as a PDF');
        $this->chunks['prop_budget']->maxPages(2);
        $this->chunks['prop_budget']->instructions($this->instructions('prop_budget'));

        $this->chunks['prop_bio'] = new Chunks\UploadPDFPlusPDFs($this, 'prop_bio', 'CV or biographical sketch');
        $this->chunks['prop_bio']->uploadFieldLabel('Upload my CV/bio as a PDF');
        $this->chunks['prop_bio']->extrasLabel('CVs or biographical sketches of collaborators');
        $this->chunks['prop_bio']->maxPages(2);
        $this->chunks['prop_bio']->extrasMaxPagesPer(2);
        $this->chunks['prop_bio']->instructions($this->instructions('prop_bio'));

        $this->chunks['prop_collaboration'] = new Chunks\UploadPDFMulti($this, 'prop_collaboration', 'Letters of collaboration');
        $this->chunks['prop_collaboration']->optional('This proposal does not require any letters of collaboration.');
        $this->chunks['prop_collaboration']->instructions($this->instructions('prop_collaboration'));

        // Maybe this isn't necessary because it's asked for on the cover page?
        // $this->chunks['prop_pastrac'] = new Chunks\TextArea($this, 'prop_pastrac', 'Previous RAC support funding');
        // $this->chunks['prop_pastrac']->instructions('');
        // $this->chunks['prop_pastrac']->optional('I have not received any RAC grants in the last 5 years.');
        // $this->chunks['prop_pastrac']->instructions($this->instructions('prop_pastrac'));

        $this->chunks['prop_conflicts'] = new Chunks\TextArea($this, 'prop_conflicts', 'Potential conflicts of interest');
        $this->chunks['prop_conflicts']->optional('I do not have any potential conflicts of interest.');
        $this->chunks['prop_conflicts']->instructions($this->instructions('prop_conflicts'));

        $this->chunks['prop_humansubjects'] = new Chunks\TextAreaPlusPDFs($this, 'prop_humansubjects', 'Description of the proposed involvement of human subjects');
        $this->chunks['prop_humansubjects']->optional('Research does not involve human subjects.');
        $this->chunks['prop_humansubjects']->instructions($this->instructions('prop_humansubjects'));
        $this->chunks['prop_humansubjects']->extrasLabel('Signed departmental review form and IRB');

        $this->chunks['prop_animalsubjects'] = new Chunks\TextAreaPlusPDFs($this, 'prop_animalsubjects', 'Description of the proposed involvement of animal subjects');
        $this->chunks['prop_animalsubjects']->optional('Research does not involve animal subjects.');
        $this->chunks['prop_animalsubjects']->instructions($this->instructions('prop_animalsubjects'));
        $this->chunks['prop_animalsubjects']->extrasLabel('Signed departmental review form and IACUC approval letter');
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
        $lname = $prop['submitter.lastname'];
        $fs = $this->submission()->cms()->helper('filestore');
        //add metadata file (includes abstract)
        $zip->addFromString(
            'appendices/_proposal info.txt',
            $this->submission()->cms()->helper('templates')->render(
                'rac/proposalinfo.txt.twig',
                [
                    'prop' => $prop,
                    'chunks' => $prop->parts()->chunks(),
                ]
            )
        );
        //checklist page
        $this->zipAddFilesChunk($zip, 'prop_checklist', $lname . ' 1 checklist - ');
        //cover page
        $this->zipAddFilesChunk($zip, 'prop_coverpage', $lname . ' 2 cover - ');
        //narrative/appendicies
        $this->zipAddFilesChunk($zip, 'prop_narrative', $lname . ' 3 narrative - ', 'appendices/narrative/' . $lname . ' - ');
        //budget
        $this->zipAddFilesChunk($zip, 'prop_budget', $lname . ' 4 budget - ', 'appendices/budget/' . $lname . ' - ');
        //bio
        $this->zipAddFilesChunk($zip, 'prop_bio', $lname . ' 5 bio - ', 'appendices/collaborators/bio - ');
        //extramural plan
        $this->zipAddFilesChunk($zip, 'prop_extramural', $lname . ' extramural plan - ');
        //extramural plan
        $this->zipAddFilesChunk($zip, 'prop_resubmission', $lname . ' resubmission intro - ');
        //letters of collaboration (placed in bio folder)
        $this->zipAddFilesChunk($zip, 'prop_collaboration', 'appendices/collaborators/letter - ');
        //potential conflicts
        $this->zipAddTextChunk($zip, 'prop_conflicts', $lname . ' potential conflicts of interest', 'appendices/conflicts/');
        //potential conflicts
        $this->zipAddTextChunk($zip, 'prop_humansubjects', $lname . ' human subjects', 'appendices/human subjects/');
        //potential conflicts
        $this->zipAddTextChunk($zip, 'prop_animalsubjects', $lname . ' animal subjects', 'appendices/animal subjects/');
    }
}
