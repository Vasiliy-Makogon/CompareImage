<?php

namespace Krugozor\Hash;

use finfo;
use GdImage;
use RuntimeException;

/**
 * Average Hash (Simple Hash)
 *
 * @author https://github.com/nvthaovn 2016
 * @author https://github.com/Vasiliy-Makogon (refactoring) 2024
 */
class AverageHash
{
    /** @var string */
    const JPG_MIME_TYPE = 'image/jpeg';

    /** @var string */
    const PNG_MIME_TYPE = 'image/png';

    /** @var int */
    const SIZE = 8;

    /**
     * @param string $source
     * @return string
     */
    public static function getHash(string $source): string
    {
        if (!file_exists($source)) {
            throw new RuntimeException(sprintf(
                '%s: File does not exist: `%s`',
                __CLASS__,
                $source
            ));
        }

        $imageResource = static::createSourceImageResource($source);
        [$width, $height] = getimagesize($source);

        $newImageResource = static::resizeSourceImage($imageResource, $width, $height);
        // convert image to grayscale
        imagefilter($newImageResource, IMG_FILTER_GRAYSCALE);
        [$average, $pixelsList] = static::colorMeanValue($newImageResource);
        $bits = static::bits($average, $pixelsList);

        return implode('', $bits);
    }

    /**
     * Compare two hashes.
     *
     * @param string $hash1
     * @param string $hash2
     * @return int different rates. if different rates <= 10 then the images are duplicate
     */
    public static function compare(string $hash1, string $hash2): int
    {
        $hashSize = static::SIZE * static::SIZE;

        if (!(strlen($hash1) == $hashSize && strlen($hash2) == $hashSize)) {
            throw new RuntimeException(sprintf(
                "%s: The hashes being checked are not in a valid format (%s character string expected)",
                __METHOD__,
                $hashSize
            ));
        }

        return count((array_diff_assoc(str_split($hash1), str_split($hash2))));
    }

    /**
     * @param string $source source file
     * @return string source file mime-type
     * @throws RuntimeException
     */
    protected static function getSourceMimeType(string $source): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $sourceMimeType = $finfo->file($source);

        if (!in_array($sourceMimeType, [static::JPG_MIME_TYPE, static::PNG_MIME_TYPE])) {
            throw new RuntimeException(sprintf(
                "%s: Unsupported image file mime-type: `%s`",
                __CLASS__,
                $sourceMimeType
            ));
        }

        return $sourceMimeType;
    }

    /**
     * @param string $source
     * @return GdImage
     * @throws RuntimeException
     */
    private static function createSourceImageResource(string $source): GdImage
    {
        return match (static::getSourceMimeType($source)) {
            static::JPG_MIME_TYPE => imagecreatefromjpeg($source),
            static::PNG_MIME_TYPE => imagecreatefrompng($source),
            default => throw new RuntimeException(sprintf(
                "%s: Unsupported image file mime-type",
                __CLASS__,
            )),
        };
    }

    /**
     * Resizes the image to a static::SIZE x static::SIZE square and returns as image resource (GdImage).
     *
     * @param GdImage $imageResource
     * @param int $width
     * @param int $height
     * @return GdImage
     * @throws RuntimeException
     */
    private static function resizeSourceImage(GdImage $imageResource, int $width, int $height): GdImage
    {
        if ($newImageResource = @imagecreatetruecolor(static::SIZE, static::SIZE)) {
            if (@imagecopyresized(
                $newImageResource,
                $imageResource,
                0,
                0,
                0,
                0,
                static::SIZE,
                static::SIZE,
                $width,
                $height
            )) {
                return $newImageResource;
            }
        }

        throw new RuntimeException(sprintf(
            '%s: Unable to create canvas', __METHOD__
        ));
    }

    /**
     * Returns the mean value of the colors and the list of all pixel colors
     *
     * @param GdImage $imageResource
     * @return array
     */
    private static function colorMeanValue(GdImage $imageResource): array
    {
        $colorList = [];
        for ($a = 0; $a < static::SIZE; $a++) {
            for ($b = 0; $b < static::SIZE; $b++) {
                $rgb = imagecolorat($imageResource, $a, $b);
                $colorList[] = $rgb & 0xFF;
            }
        }

        return [floor(array_sum($colorList) / (static::SIZE * static::SIZE)), $colorList];
    }

    /**
     * Returns an array with 1 and zeros.
     * If a color is bigger than the mean value of colors it is 1
     *
     * @param int|float $average
     * @param array $pixelsList
     * @return array
     */
    private static function bits(int|float $average, array $pixelsList): array
    {
        return array_map(function ($value) use ($average) {
            return (int) ($value >= $average);
        }, $pixelsList);
    }
}