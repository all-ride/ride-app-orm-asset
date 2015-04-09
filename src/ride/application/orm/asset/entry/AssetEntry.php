<?php

namespace ride\application\orm\asset\entry;

use ride\application\orm\entry\AssetEntry as OrmAssetEntry;

/**
 * Data container for a asset object
 */
class AssetEntry extends OrmAssetEntry {

    /**
     * Audio type
     * @var string
     */
    const TYPE_AUDIO = 'audio';

    /**
     * Image type
     * @var string
     */
    const TYPE_IMAGE = 'image';

    /**
     * Pdf type
     * @var string
     */
    const TYPE_PDF = 'pdf';

    /**
     * Unknown type
     * @var string
     */
    const TYPE_UNKNOWN = 'unknown';

    /**
     * Video type
     * @var string
     */
    const TYPE_VIDEO = 'video';

    /**
     * Flag to see if the media of this asset has been parsed
     * @var boolean
     */
    protected $isParsed = true;

    /**
     * Checks if this asset comes from an URL
     * return boolean
     */
    public function isUrl() {
        return filter_var($this->getValue(), FILTER_VALIDATE_URL);
    }

    /**
     * Gets whether this asset is audio
     * @return boolean
     */
    public function isAudio() {
        return $this->getType() == self::TYPE_AUDIO;
    }

    /**
     * Gets whether this asset is an image
     * @return boolean
     */
    public function isImage() {
        return $this->getType() == self::TYPE_IMAGE;
    }

    /**
     * Gets whether this asset is video
     * @return boolean
     */
    public function isVideo() {
        return $this->getType() == self::TYPE_VIDEO;
    }

    /**
     * Gets whether the media of this asset has been parsed
     * @return boolean
     */
    public function isParsed() {
        return $this->isParsed;
    }

    /**
     * Sets whether the media of this asset has been parsed
     * @param boolean $isParsed
     * @return null
     */
    public function setIsParsed($isParsed) {
        $this->isParsed = $isParsed;
    }

    /**
     * Updates the value of the asset, setting isParsed flag
     * @param string $value
     * @return null
     */
    public function setValue($value) {
        if (!$this->getId()) {
            $this->setIsParsed(false);
        } else {
            $oldValue = $this->getValue();

            if ($oldValue === null || $oldValue === $value) {
                $this->setIsParsed(true);
            } else {
                $this->setIsParsed(false);
            }
        }

        parent::setValue($value);
    }

    /**
     * Updates the thumbnail of the asset, setting isParsed flag
     * @param string $thumbnail
     * @return null
     */
    public function setThumbnail($thumbnail) {
        if (!$this->getId()) {
            $this->setIsParsed(false);
        } else {
            $oldThumbnail = $this->getThumbnail();
            if ($thumbnail && $oldThumbnail === $thumbnail) {
                $this->setIsParsed(true);
            } else {
                $this->setIsParsed(false);
            }
        }

        parent::setThumbnail($thumbnail);
    }

    /**
     * Gets the image
     * @return string|null Path to the image if available, null otherwise
     */
    public function getImage() {
        if ($this->getType() == self::TYPE_IMAGE) {
            return $this->getValue();
        } else {
            return $this->getThumbnail();
        }
    }

    /**
     * Gets the image of a style
     * @return string|null Path to the image if available, null otherwise
     */
    public function getStyleImage($name) {
        $style = $this->getStyle($name);
        if ($style) {
            return $style->getImage();
        }

        return null;
    }

    /**
     * Gets a style by name
     * @return \ride\application\orm\entry\AssetImageStyleEntry|null
     */
    public function getStyle($name) {
        $assetStyles = $this->getStyles();
        foreach ($assetStyles as $assetStyle) {
            $style = $assetStyle->getStyle();
            if ($style->getName() == $name || $style->getSlug() == $name) {
                return $assetStyle;
            }
        }

        return null;
    }

}
