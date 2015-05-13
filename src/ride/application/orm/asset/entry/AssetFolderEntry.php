<?php

namespace ride\application\orm\asset\entry;

use ride\application\orm\asset\model\AssetFolderModel;
use ride\application\orm\entry\AssetFolderEntry as OrmAssetFolderEntry;

/**
 * Data container for a asset folder
 */
class AssetFolderEntry extends OrmAssetFolderEntry {

    /**
     * Variable to attach the children of this folder to
     * @var array
     */
    protected $children;

    /**
     * Get a string representation of the folder
     * @return string
     */
    public function __toString() {
        return $this->getPath() . ': ' . $this->getName();
    }

    /**
     * Gets the type of this entry
     * @return string
     */
    public function getType() {
        return 'folder';
    }

    /**
     * Get the full path of the folder. The path is used for the parent field of a folder.
     * @return string
     */
    public function getPath() {
        if (!$this->getParent()) {
            return $this->id;
        }

        return $this->getParent() . AssetFolderModel::PATH_SEPARATOR . $this->getId();
    }

    /**
     * Get the folder id of the root of this folder
     * @return integer
     */
    public function getRootFolderId() {
        if (!$this->getParent()) {
            return $this->getId();
        }

        $tokens = explode(AssetFolderModel::PATH_SEPARATOR, $this->getParent());

        return array_shift($tokens);
    }

    /**
     * Get the folder id of the parent
     * @return integer
     */
    public function getParentFolderId() {
        if (!$this->getParent()) {
            return null;
        }

        $ids = explode(AssetFolderModel::PATH_SEPARATOR, $this->getParent());

        return array_pop($ids);
    }

    /**
     * Checks if the provided folder is a parent folder of this folder
     * @param AssetFolderEntry $folder The folder to check as a parent
     * @return boolean True if the provided folder is a parent, false otherwise
     */
    public function hasParentFolder(AssetFolderEntry $folder) {
        $ids = explode(AssetFolderModel::PATH_SEPARATOR, $this->parent);

        return in_array($folder->getId(), $ids);
    }

    /**
     * Gets the level of this folder
     * @return integer
     */
    public function getLevel() {
        if (!$this->getParent()) {
            return 0;
        }

        return substr_count($this->getParent(), AssetFolderModel::PATH_SEPARATOR) + 1;
    }

    /**
     * Sets the children of this folder
     * @param array $children
     * @return null
     */
    public function setChildren(array $children) {
        $this->children = $children;
    }

    /**
     * Gets the children of this folder
     * @return array|null
     */
    public function getChildren() {
        return $this->children;
    }

}
