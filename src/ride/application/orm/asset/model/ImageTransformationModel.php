<?php

namespace ride\application\orm\asset\model;

use ride\library\i18n\translator\Translator;
use ride\library\orm\model\GenericModel;

/**
 * Model for the image transformations
 */
class ImageTransformationModel extends GenericModel {

    /**
     * Gets the possible transformations
     * @param \ride\library\i18n\translator\Translator $translator
     * @return array
     */
    public function getTransformations(Translator $translator) {
        $result = array();

        $transformations = $this->orm->getDependencyInjector()->getAll('ride\\library\\image\\transformation\\Transformation');
        foreach ($transformations as $id => $transformation) {
            $result[$id] = $translator->translate('label.image.transformation.' . $id);
        }

        return $result;
    }

}
