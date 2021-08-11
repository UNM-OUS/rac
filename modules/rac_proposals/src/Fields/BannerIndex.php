<?php
namespace Digraph\Modules\rac_proposals\Fields;

class BannerIndex extends \Formward\Fields\Input
{
    const BANNER_IDX_REGEX = '[0-9]{6}';

    public function construct()
    {
        //pattern attribute for browser checking
        $this->attr('pattern', static::BANNER_IDX_REGEX);
        //validator for server side checking
        $this->addValidatorFunction('validbannerindex', function ($field) {
            if ($v = $field->value()) {
                if (!preg_match('/^' . $field::BANNER_IDX_REGEX . '$/', $v)) {
                    return 'Banner indexes should be exactly six numeric digits';
                }
            }
            return true;
        });
        //tip explaining expected format
        $this->addTip('Banner indexes should be exactly six numeric digits');
    }
}
