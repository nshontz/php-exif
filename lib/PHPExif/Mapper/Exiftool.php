<?php
/**
 * PHP Exif Exiftool Mapper
 *
 * @link        http://github.com/miljar/PHPExif for the canonical source repository
 * @copyright   Copyright (c) 2015 Tom Van Herreweghe <tom@theanalogguy.be>
 * @license     http://github.com/miljar/PHPExif/blob/master/LICENSE MIT License
 * @category    PHPExif
 * @package     Mapper
 */

namespace PHPExif\Mapper;

use PHPExif\Exif;
use DateTime;

/**
 * PHP Exif Exiftool Mapper
 *
 * Maps Exiftool raw data to valid data for the \PHPExif\Exif class
 *
 * @category    PHPExif
 * @package     Mapper
 */
class Exiftool implements MapperInterface
{
    const APERTURE                 = 'Aperture';
    const APPROXIMATEFOCUSDISTANCE = 'ApproximateFocusDistance';
    const ARTIST                   = 'Artist';
    const CAPTION                  = 'Caption';
    const CAPTIONABSTRACT          = 'Caption-Abstract';
    const COLORSPACE               = 'ColorSpace';
    const COPYRIGHT                = 'Copyright';
    const CREATEDATE               = 'CreateDate';
    const CREDIT                   = 'Credit';
    const EXPOSURETIME             = 'ExposureTime';
    const FILESIZE                 = 'FileSize';
    const FOCALLENGTH              = 'FocalLength';
    const HEADLINE                 = 'Headline';
    const IMAGEHEIGHT              = 'ImageHeight';
    const IMAGEWIDTH               = 'ImageWidth';
    const ISO                      = 'ISO';
    const JOBTITLE                 = 'JobTitle';
    const KEYWORDS                 = 'Keywords';
    const MIMETYPE                 = 'MIMEType';
    const MODEL                    = 'Model';
    const ORIENTATION              = 'Orientation';
    const SOFTWARE                 = 'Software';
    const SOURCE                   = 'Source';
    const TITLE                    = 'Title';
    const XRESOLUTION              = 'XResolution';
    const YRESOLUTION              = 'YResolution';
    const GPSLATITUDE              = 'GPSLatitude';
    const GPSLONGITUDE             = 'GPSLongitude';

    /**
     * Maps the ExifTool fields to the fields of
     * the \PHPExif\Exif class
     *
     * @var array
     */
    protected $map = array(
        self::APERTURE                 => Exif::APERTURE,
        self::ARTIST                   => Exif::AUTHOR,
        self::MODEL                    => Exif::CAMERA,
        self::CAPTION                  => Exif::CAPTION,
        self::COLORSPACE               => Exif::COLORSPACE,
        self::COPYRIGHT                => Exif::COPYRIGHT,
        self::CREATEDATE               => Exif::CREATION_DATE,
        self::CREDIT                   => Exif::CREDIT,
        self::EXPOSURETIME             => Exif::EXPOSURE,
        self::FILESIZE                 => Exif::FILESIZE,
        self::FOCALLENGTH              => Exif::FOCAL_LENGTH,
        self::APPROXIMATEFOCUSDISTANCE => Exif::FOCAL_DISTANCE,
        self::HEADLINE                 => Exif::HEADLINE,
        self::IMAGEHEIGHT              => Exif::HEIGHT,
        self::XRESOLUTION              => Exif::HORIZONTAL_RESOLUTION,
        self::ISO                      => Exif::ISO,
        self::JOBTITLE                 => Exif::JOB_TITLE,
        self::KEYWORDS                 => Exif::KEYWORDS,
        self::MIMETYPE                 => Exif::MIMETYPE,
        self::ORIENTATION              => Exif::ORIENTATION,
        self::SOFTWARE                 => Exif::SOFTWARE,
        self::SOURCE                   => Exif::SOURCE,
        self::TITLE                    => Exif::TITLE,
        self::YRESOLUTION              => Exif::VERTICAL_RESOLUTION,
        self::IMAGEWIDTH               => Exif::WIDTH,
        self::CAPTIONABSTRACT          => Exif::CAPTION,
        self::GPSLATITUDE              => self::GPSLATITUDE,
        self::GPSLONGITUDE             => self::GPSLONGITUDE,
    );

    /**
     * @var bool
     */
    protected $numeric = true;

    /**
     * Maps an Exiftool field to a method to manipulate the data
     * for the \PHPExif\Exif class
     *
     * @var array
     */
    protected $manipulators = array(
        self::APERTURE                 => 'convertAperture',
        self::APPROXIMATEFOCUSDISTANCE => 'convertFocusDistance',
        self::CREATEDATE               => 'convertCreateDate',
        self::EXPOSURETIME             => 'convertExposureTime',
        self::FOCALLENGTH              => 'convertFocalLength',
        self::GPSLATITUDE              => 'extractGPSCoordinates',
        self::GPSLONGITUDE             => 'extractGPSCoordinates',
    );

    /**
     * Mutator method for the numeric property
     *
     * @param bool $numeric
     * @return \PHPExif\Mapper\Exiftool
     */
    public function setNumeric($numeric)
    {
        $this->numeric = (bool)$numeric;

        return $this;
    }

    /**
     * Maps the array of raw source data to the correct
     * fields for the \PHPExif\Exif class
     *
     * @param array $data
     * @return array
     */
    public function mapRawData(array $data)
    {
        $mappedData = array();
        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $this->map)) {
                // silently ignore unknown fields
                continue;
            }

            $key = $this->map[$field];

            // manipulate the data
            if (array_key_exists($field, $this->manipulators)) {
                $method = $this->manipulators[$field];
                $value = $this->$method($value);
            }

            // set end result
            $mappedData[$key] = $value;
        }

        // add GPS coordinates, if available
        $mappedData = $this->mapGPSData($data, $mappedData);

        return $mappedData;
    }

    /**
     * Maps GPS data to the correct key, if such data exists
     *
     * @param array $data
     * @param array $mappedData
     * @return array
     */
    protected function mapGPSData(array $data, array $mappedData)
    {
        if (!array_key_exists(self::GPSLATITUDE, $mappedData)
        || !array_key_exists(self::GPSLONGITUDE, $mappedData)) {
            unset($mappedData[self::GPSLATITUDE]);
            unset($mappedData[self::GPSLONGITUDE]);

            return $mappedData;
        }

        $latitude = $mappedData[self::GPSLATITUDE];
        $longitude = $mappedData[self::GPSLONGITUDE];

        if ($latitude === false || $longitude === false) {
            unset($mappedData[self::GPSLATITUDE]);
            unset($mappedData[self::GPSLONGITUDE]);

            return $mappedData;
        }

        $gpsLocation = sprintf(
            '%s,%s',
            (strtoupper($data['GPSLatitudeRef'][0]) === 'S' ? -1 : 1) * $latitude,
            (strtoupper($data['GPSLongitudeRef'][0]) === 'W' ? -1 : 1) * $longitude
        );

        unset($mappedData[self::GPSLATITUDE]);
        unset($mappedData[self::GPSLONGITUDE]);
        $mappedData[Exif::GPS] = $gpsLocation;

        return $mappedData;
    }

    /**
     * Converts incoming aperture value to a sensible format
     *
     * @param string $originalValue
     * @return string
     */
    protected function convertAperture($originalValue)
    {
        return sprintf('f/%01.1f', $originalValue);
    }

    /**
     * Converts incoming focus distance value to a sensible format
     *
     * @param string $originalValue
     * @return string
     */
    protected function convertFocusDistance($originalValue)
    {
        return sprintf('%1$sm', $originalValue);
    }

    /**
     * Converts incoming Exiftool date to a DateTime object
     *
     * @param string $originalValue
     * @return \DateTime
     */
    protected function convertCreateDate($originalValue)
    {
        return DateTime::createFromFormat('Y:m:d H:i:s', $originalValue);
    }

    /**
     * Converts incoming exposure time to a sensible format
     *
     * @param string $originalValue
     * @return string
     */
    protected function convertExposureTime($originalValue)
    {
        return '1/' . round(1 / $originalValue);
    }

    /**
     * Converts focal length to a float value
     *
     * @param string $originalValue
     * @return float
     */
    protected function convertFocalLength($originalValue)
    {
        $focalLengthParts = explode(' ', $originalValue);

        return (int) reset($focalLengthParts);
    }

    /**
     * Extract GPS coordinates from formatted string
     *
     * @param string $coordinates
     * @return array
     */
    protected function extractGPSCoordinates($coordinates)
    {
        if ($this->numeric === true) {
            return abs((float) $coordinates);
        } else {
            if (!preg_match('!^([0-9.]+) deg ([0-9.]+)\' ([0-9.]+)"!', $coordinates, $matches)) {
                return false;
            }

            return intval($matches[1]) + (intval($matches[2]) / 60) + (floatval($matches[3]) / 3600);
        }
    }
}
