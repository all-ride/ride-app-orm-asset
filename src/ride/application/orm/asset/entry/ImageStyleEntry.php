<?php

namespace ride\application\orm\asset\entry;

use ride\application\orm\entry\ImageStyleEntry as OrmImageStyleEntry;

/**
 * Data container for a image style
 */
class ImageStyleEntry extends OrmImageStyleEntry {

    /**
     * Gets a string representation of this image style
     * @return string
     */
    public function __toString() {
        return $this->getFriendlyName();
    }

    /**
     * Gets a human friendly name of this image style
     * @return string
     */
    public function getFriendlyName() {
        $name = $this->getDisplayName();
        if (!$name) {
            $name = $this->getName();
        }

        return $name;
    }

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

    /**
     * Gets an array with resize and crop transformation definitions
     * @return array Array with the machine name of the transformation as key
     * and the an option array as value
     */
    public function getSizeTransformationArray() {
        if (isset($this->sizeTransformationArray)) {
            return $this->siteTransformationArray;
        }

        $transformationArray = $this->getTransformationArray();
        foreach ($transformationArray as $key => $value) {
            if ($key == 'resize' || $key == 'crop') {
                continue;
            }

            unset($transformationArray[$key]);
        }

        $this->sizeTransformationArray = $transformationArray;

        return $this->sizeTransformationArray;
    }

}
