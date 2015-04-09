<?php

namespace ride\application\orm\asset\entry;

use ride\application\orm\entry\ImageStyleEntry as OrmImageStyleEntry;

/**
 * Data container for a image style
 */
class ImageStyleEntry extends OrmImageStyleEntry {

    /**
     * Gets an array with transformation definition
     * @return array Array with the machine name of the transformation as key
     * and the an option array as value
     */
    public function getTransformationArray() {
        $result = array();

        $transformations = $this->getTransformations();
        foreach ($transformations as $transformation) {
            $options = array();

            $transformationOptions = $transformation->getOptions();
            foreach ($transformationOptions as $transformationOption) {
                $options[$transformationOption->getKey()] = $transformationOption->getValue();
            }

            $result[$transformation->getTransformation()] = $options;
        }

        return $result;
    }

}
