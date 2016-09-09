<?php

namespace ride\application\orm\asset\model;

use ride\application\orm\asset\entry\AssetEntry;
use ride\application\orm\asset\entry\AssetFolderEntry;

use ride\library\http\Header;
use ride\library\image\exception\ImageException;
use ride\library\i18n\translator\Translator;
use ride\library\media\exception\UnsupportedMediaException;
use ride\library\orm\model\GenericModel;

use \Exception;

/**
 * Model for the asset items
 */
class AssetModel extends GenericModel {

    /**
     * Name of the file source
     * @var string
     */
    const SOURCE_FILE = 'file';

    /**
     * Name of the URL source
     * @var string
     */
    const SOURCE_URL = 'url';

    /**
     * Gets an entry list as a flat list
     * @param string $locale Code of the locale
     * @param boolean $fetchUnlocalized Flag to see if unlocalized entries
     * should be fetched
     * @return array Array with the id of the entry as key and the title format
     * as value
     */
    public function getEntryList($locale = null, $fetchUnlocalized = false) {
        $locale = $this->getLocale($locale);

        if (isset($this->list[$locale])) {
            return $this->list[$locale];
        }

        $page = 1;
        $limit = 1000;
        $this->list[$locale] = array();

        do {
            $query = $this->createFindQuery(null, $locale, $fetchUnlocalized);
            $query->setFields('{id}, {name}');
            $query->setLimit($limit, ($page - 1) * $limit);
            $entries = $query->query();

            $this->list[$locale] += $this->getOptionsFromEntries($entries);

            $page++;
        } while (count($entries) == $limit);

        return $this->list[$locale];
    }

    /**
     * Gets the assets for a folder
     * @param mixed $folder Array of folder ids, instance of folder or a folder
     * id
     * @param string $locale Code of the locale
     * @param boolean|null $fetchUnlocalized
     * @param array $filter
     * @return array
     */
    public function getByFolder($folder, $locale = null, $fetchUnlocalized = null, array $filter = null, $limit = 0, $page = 1, $offset = 0) {
        $query = $this->createByFolderQuery($folder, $locale, $fetchUnlocalized, $filter);
        if ($limit) {
            $query->setLimit($limit, (($page - 1) * $limit) + $offset);
        }

        return $query->query();
    }

    /**
     * Gets the assets for a folder
     * @param mixed $folder Array of folder ids, instance of folder or a folder
     * id
     * @param string $locale Code of the locale
     * @param boolean|null $fetchUnlocalized
     * @param array $filter
     * @return array
     */
    public function countByFolder($folder, $locale = null, $fetchUnlocalized = null, array $filter = null) {
        $query = $this->createByFolderQuery($folder, $locale, $fetchUnlocalized, $filter);

        return $query->count();
    }

    /**
     * Gets the assets for a folder
     * @param mixed $folder Array of folder ids, instance of folder or a folder
     * id
     * @param string $locale Code of the locale
     * @param boolean|null $fetchUnlocalized
     * @param array $filter
     * @return array
     */
    protected function createByFolderQuery($folder, $locale = null, $fetchUnlocalized = null, array $filter = null) {
        $query = $this->createQuery($locale);
        $query->setDistinct(true);
        $query->setFetchUnlocalized($fetchUnlocalized);
        $query->addOrderBy('{orderIndex} ASC');

        if (is_array($folder)) {
            $query->addCondition('{folder} IN %1%', $folder);
        } elseif (!$folder || !$folder->getId()) {
            $query->addCondition('{folder} IS NULL');
        } else {
            $query->addCondition('{folder} = %1%', $folder);
        }

        if (isset($filter['query'])) {
            $query->addCondition('{name} LIKE %1% OR {description} LIKE %1%', '%' . $filter['query'] . '%');
        }

        if (isset($filter['type']) && $filter['type'] != 'all') {
            $query->addCondition('{type} = %1%', $filter['type']);
        }

        if (isset($filter['date']) && $filter['date'] != 'all') {
            if ($filter['date'] == 'today') {
                $filter['date'] = date('Y-m-d');
            }

            $tokens = explode('-', $filter['date']);
            $numTokens = count($tokens);
            if ($numTokens == 1) {
                // year
                $from = mktime(0, 0, 0, 1, 1, $tokens[0]);
                $till = mktime(23, 59, 59, 12, 31, $tokens[0]);
            } elseif ($numTokens == 2) {
                // month
                $from = mktime(0, 0, 0, $tokens[1], 1, $tokens[0]);
                $till = mktime(23, 59, 59, $tokens[1], date('t', $from), $tokens[0]);
            } else {
                $from = mktime(0, 0, 0, $tokens[1], $tokens[2], $tokens[0]);
                $till = mktime(23, 59, 59, $tokens[1], $tokens[2], $tokens[0]);
            }

            $query->addCondition('%1% <= {dateAdded} AND {dateAdded} <= %2%', $from, $till);
        }

        return $query;
    }

    /**
     * Gets the dimension of the provided asset
     * @param \ride\application\orm\asset\entry\AssetEntry $asset
     * @return \ride\library\image\dimension\Dimension|null
     */
    public function getDimension(AssetEntry $asset) {
        if (!$asset->isImage()) {
            return null;
        }

        $file = $this->getFileBrowser()->getFile($asset->getValue());
        if (!$file) {
            return null;
        }

        $image = $this->getImageFactory()->createImage();
        $image->read($file);

        return $image->getDimension();
    }

    /**
     * Gets all the used types
     * @param \ride\library\i18n\translator\Translator $translator
     * @return array Array with the type as key and the translated type as value
     */
    public function getTypes(Translator $translator) {
        $query = $this->createQuery();
        $query->setFields('{type}');
        $query->setDistinct(TRUE);

        $types = $query->query('type');
        foreach ($types as $type => $null) {
            if (!$type) {
                continue;
            }

            $types[$type] = $translator->translate('label.type.' . $type);
        }

        asort($types);

        return $types;
    }

    /**
     * Gets all the dates of upload
     * @return array Array with the numeric year and month as key and formatted
     * as value
     */
    public function getMonths() {
        $query = $this->createQuery();
        $query->setFields('FROM_UNIXTIME({dateAdded}, "%Y-%m") AS month');
        $query->setDistinct(TRUE);
        $query->addOrderBy('month DESC');

        $months = $query->query('month');
        foreach ($months as $key => $null) {
            list($year, $month) = explode('-', $key);

            $months[$key] = strftime('%B %Y', mktime(12, 0, 0, $month, 1, $year));
        }

        return $months;
    }

    /**
     * Moves assets to another folder
     * @param \ride\application\orm\asset\entry\AssetEntry|array $assets Asset or
     * an array of assets to move
     * @param \ride\application\orm\asset\entry\AssetFolderEntry $destination
     * @return null
     */
    public function move($assets, AssetFolderEntry $destination = null) {
        if (!is_array($assets)) {
            $assets = array($assets);
        }

        $sources = array();
        $orderIndex = $this->getNewOrderIndex($destination);

        foreach ($assets as $asset) {
            if (!$asset->getFolder() && !$destination || ($asset->getFolder() && $destination && $asset->getFolder()->getId() == $destination->getId())) {
                continue;
            }

            $source = $asset->getFolder();
            $sources[$source ? $source->getId() : 0] = $source;

            $asset->setFolder($destination);
            $asset->setOrderIndex($orderIndex);

            $orderIndex++;
        }

        $isTransactionStarted = $this->beginTransaction();
        try {
            $this->save($assets);

            foreach ($sources as $source) {
                $this->orderFolder($source);
            }

            $this->commitTransaction($isTransactionStarted);
        } catch (Exception $exception) {
            $this->rollbackTransaction($isTransactionStarted);

            throw $exception;
        }
    }

    /**
     * Orders the provided items in the order they are provided
     * @param array $assets
     * @return null
     */
    public function order(array $assets) {
        $indexes = array();
        foreach ($assets as $asset) {
            $orderIndex = $asset->getOrderIndex();
            if (isset($indexes[$orderIndex])) {
                throw new Exception('Could not order the assets: weight ' . $orderIndex . ' is used by more then 1 asset');
            }

            $indexes[$orderIndex] = true;
        }

        ksort($indexes);
        $indexes = array_keys($indexes);

        $isTransactionStarted = $this->beginTransaction();
        try {
            foreach ($assets as $asset) {
                $asset->setOrderIndex(array_shift($indexes));

                $this->save($asset);
            }

            $this->commitTransaction($isTransactionStarted);
        } catch (Exception $exception) {
            $this->rollbackTransaction($isTransactionStarted);

            throw $exception;
        }
    }

    /**
     * Orders the items in the provided parent with the provided algorithm
     * @param \ride\application\orm\asset\entry\AssetFolderEntry $parent Parent
     * of the items to order
     * @param string $order Name of the order algorithm
     * @return null
     */
    public function orderFolder(AssetFolderEntry $parent = null, $order = AssetFolderModel::ORDER_RESYNC) {
        $index = 1;
        $ordered = array();

        $assets = $this->getByFolder($parent);
        switch ($order) {
            case AssetFolderModel::ORDER_ASC:
            case AssetFolderModel::ORDER_DESC:
                foreach ($assets as $asset) {
                    $base = $asset->getName();
                    $name = $base;
                    $index = 1;

                    while (isset($ordered[$name])) {
                        $name = $base . '-' . $index;
                        $index++;
                    }

                    $ordered[$name] = $asset;
                }

                break;
            case AssetFolderModel::ORDER_NEWEST:
            case AssetFolderModel::ORDER_OLDEST:
                foreach ($assets as $asset) {
                    $ordered[$asset->getDateAdded()] = $asset;
                }

                break;
            case AssetFolderModel::ORDER_RESYNC:
                foreach ($assets as $asset) {
                    $ordered[] = $asset;
                }

                break;
            default:
                throw new Exception('Could not order the assets: invalid order method provided');
        }

        ksort($ordered);
        if ($order == AssetFolderModel::ORDER_DESC || $order == AssetFolderModel::ORDER_OLDEST) {
            $ordered = array_reverse($ordered);
        }

        $this->order($ordered);
    }

    /**
     * Saves a asset to the model
     * @param \ride\application\orm\asset\entry\AssetEntry $asset
     * @return null
     */
    protected function saveEntry($asset) {
        if (!$asset->getId() && !$asset->getOrderIndex()) {
            $asset->setOrderIndex($this->getNewOrderIndex($asset->getFolder()));
        }

        if (!$asset->isParsed() || !$asset->getName()) {
            $this->parseAsset($asset);
        }

        parent::saveEntry($asset);
    }

    /**
     * Gets an order index for a new item in a folder
     * @param \ride\application\orm\asset\entry\AssetFolderEntry $parent Parent
     * to get a new order index in
     * @return integer new order index
     */
    protected function getNewOrderIndex(AssetFolderEntry $parent = null) {
        $query = $this->createQuery();
        $query->setFields('MAX({orderIndex}) AS maxOrderIndex');

        if ($parent) {
            $query->addCondition('{folder} = %1%', $parent->getId());
        } else {
            $query->addCondition('{folder} IS NULL');
        }

        $result = $query->queryFirst();

        return $result->maxOrderIndex + 1;
    }

    /**
     * Parses the file or URL of the provided asset
     * @param \ride\application\orm\asset\entry\AssetEntry $asset Asset to parse
     * @return null
     */
    public function parseAsset(AssetEntry $asset) {
        if (!$asset->getValue()) {
            return;
        }

        if ($asset->isUrl()) {
            $this->parseUrl($asset);
        } else {
            $this->parseFile($asset);
        }

        $asset->setIsParsed(true);
    }

    /**
     * Parses the URL of the provided asset
     * @param \ride\application\orm\asset\entry\AssetEntry $asset Asset to parse
     * @return null
     */
    protected function parseUrl($asset) {
        $mediaFactory = $this->getMediaFactory();

        try {
            $media = $mediaFactory->createMediaItem($asset->getValue());

            $asset->setSource($media->getType());
            $asset->setEmbedUrl($media->getEmbedUrl());
            $asset->setThumbnail(null);

            if ($media->isVideo()) {
                $asset->setType(AssetEntry::TYPE_VIDEO);
            } elseif ($media->isAudio()) {
                $asset->setType(AssetEntry::TYPE_AUDIO);
            } elseif ($media->isImage()) {
                $asset->setType(AssetEntry::TYPE_IMAGE);
            } elseif ($media->isDocument()) {
                $asset->setType(AssetEntry::TYPE_DOCUMENT);
            } else {
                $asset->setType(AssetEntry::TYPE_UNKNOWN);
            }

            if (!$asset->getName() && $this->useMediaProperty($media->getType(), 'name')) {
                $asset->setName($media->getTitle());
            }

            if (!$asset->getDescription() && $this->useMediaProperty($media->getType(), 'description')) {
                $asset->setDescription($media->getDescription());
            }

            if ($media->getThumbnailUrl() && $this->useMediaProperty($media->getType(), 'thumbnail')) {
                $client = $mediaFactory->getHttpClient();
                $response = $client->get($media->getThumbnailUrl());

                if ($response->isOk()) {
                    $mediaType = $response->getHeader(Header::HEADER_CONTENT_TYPE);
                    $extension = $this->getMimeService()->getExtensionForMediaType($mediaType);

                    $directory = $this->getDirectory();
                    $file = $directory->getChild($media->getId() . '.' . $extension);
                    $file->write($response->getBody());

                    $file = $this->getFileBrowser()->getRelativeFile($file, true);

                    $asset->setThumbnail($file->getPath());
                }
            }
        } catch (UnsupportedMediaException $exception) {
            $asset->setSource(self::SOURCE_URL);
            $asset->setType(AssetEntry::TYPE_UNKNOWN);
            $asset->setThumbnail(null);

            if (!$asset->getName()) {
                $asset->setName($asset->getValue());
            }
        }
    }

    /**
     * Checks if the provided property should be set through the obtained media
     * object
     * @param string $source Source of the media object
     * @param string $property Name of the property
     * @return boolean
     */
    protected function useMediaProperty($source, $property) {
        return $this->getConfig()->get('asset.media.' . $source . '.' . $property, true);
    }

    /**
     * Parses the file of the provided asset
     * @param \ride\application\orm\asset\entry\AssetEntry $asset Asset to parse
     * @return null
     */
    protected function parseFile($asset) {
        $file = $this->getFileBrowser()->getFile($asset->getValue());
        if (!$file) {
            $asset->setValue(null);

            return;
        }

        $mediaType = $this->getMimeService()->getMediaTypeForFile($file);

        $asset->setMime((string) $mediaType);
        $asset->setSource(self::SOURCE_FILE);
        $asset->setThumbnail(null);

        if (!$asset->getName()) {
            $asset->setName($file->getName(true));
        }

        if ($mediaType->isImage()) {
            $asset->setType(AssetEntry::TYPE_IMAGE);
            $asset->setThumbnail($asset->getValue());
        } elseif ($mediaType->isAudio()) {
            $asset->setType(AssetEntry::TYPE_AUDIO);
        } elseif ($mediaType->isVideo()) {
            $asset->setType(AssetEntry::TYPE_VIDEO);
        } elseif ($mediaType->getType() == 'application') {
            $asset->setType(AssetEntry::TYPE_DOCUMENT);
        } else {
            $asset->setType(AssetEntry::TYPE_UNKNOWN);
        }
    }

    /**
     * Gets the directory of the assets
     * @return \ride\library\system\file\File
     */
    public function getDirectory() {
        return $this->orm->getDependencyInjector()->get('ride\\library\\system\\file\\File', 'assets');
    }

    /**
     * Gets the MIME service
     * @return \ride\service\MimeService
     */
    public function getMimeService() {
        return $this->orm->getDependencyInjector()->get('ride\\service\\MimeService');
    }

    /**
     * Gets the image factory
     * @return \ride\library\image\ImageFactory
     */
    public function getImageFactory() {
        return $this->orm->getDependencyInjector()->get('ride\\library\\image\\ImageFactory');
    }

    /**
     * Gets the media factory
     * @return \ride\library\system\file\browser\FileBrowser
     */
    public function getMediaFactory() {
        return $this->orm->getDependencyInjector()->get('ride\\library\\media\\MediaFactory');
    }

    /**
     * Gets the file browser
     * @return \ride\library\system\file\browser\FileBrowser
     */
    public function getFileBrowser() {
        return $this->orm->getDependencyInjector()->get('ride\\library\\system\\file\\browser\\FileBrowser');
    }

    /**
     * Gets the global config
     * @return \ride\library\config\Config
     */
    public function getConfig() {
        return $this->orm->getDependencyInjector()->get('ride\\library\\config\\Config');
    }

}
