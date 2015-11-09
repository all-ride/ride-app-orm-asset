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
        if (isset($this->transformationArray)) {
            return $this->transformationArray;
        }

        $this->transformationArray = array();

        $transformations = $this->getTransformations();
        foreach ($transformations as $transformation) {
            $options = array();

            $transformationOptions = $transformation->getOptions();
            foreach ($transformationOptions as $transformationOption) {
                $options[$transformationOption->getKey()] = $transformationOption->getValue();
            }

            $this->transformationArray[$transformation->getTransformation()] = $options;
        }

        return $this->transformationArray;
    }

}
