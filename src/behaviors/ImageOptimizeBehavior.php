<?php
/**
 * Created by PhpStorm.
 * User: karlen
 * Date: 31.08.2017
 * Time: 08:06
 */

namespace simialbi\yii2\elfinder\behaviors;

use simialbi\yii2\elfinder\base\ElFinderEvent;
use simialbi\yii2\elfinder\ElFinder;
use yii\base\Behavior;

/**
 * ImageOptimizeBehavior automatically optimizes images after upload before saving in elFinder.
 *
 * To use ImageOptimizeBehavior, configure the ElFinder Component to attach this behavior.
 *
 * ```php
 * use simialbi\yii2\elfinder\behaviors;
 *
 * [
 *      'modules' => [
 *          'class' => 'simialbi\yii2\elfinder\Module',
 *          'connectionSets' => [...],
 *          'volumeBehaviors' => [
 *              'default' => [
 *                  'as image_behavior' => [
 *                      'class' => 'simialbi\yii2\elfinder\behaviors\ImageOptimizeBehavior',
 *                      // 'jpegOmptimizer' => 'jpegtran -copy none -optimize -progressive -outfile {to} {from}' // optional override process
 *                      // 'pngOptimizer' => 'optipng -o7 -strip all -out {to} {from}' // optional override process
 *                  ]
 *              ]
 *          ]
 *      ]
 * ]
 * ```
 *
 * @package simialbi\yii2\elfinder\behaviors
 * @author Simon Karlen <simi.albi@gmail.com>
 */
class ImageOptimizeBehavior extends Behavior
{
    use BehaviorTrait;

    /**
     * @var string
     */
    public string $jpegOptimizer = 'jpegtran -copy none -optimize -progressive -outfile {to} {from}';

    /**
     * @var string
     */
    public string $pngOptimizer = 'optipng -o2 -strip all -out {to} {from}';

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            ElFinder::EVENT_UPLOAD_BEFORE_SAVE => 'afterUploadBeforeSave'
        ];
    }

    /**
     * @param ElFinderEvent $event
     */
    public function afterUploadBeforeSave(ElFinderEvent $event): void
    {
//		$elfinder = $event->sender;
        $src = $event->fileTmpName;
//		$volume   = ArrayHelper::getValue($event->data, 'volume');
        /* @var $elfinder \elFinder */
        /* @var $volume \elFinderVolumeDriver */

        if (!file_exists($src)) {
            return;
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($src);
            if (!str_starts_with($mime, 'image')) {
                return;
            }
        }

        $srcImgInfo = getimagesize($src);
        if ($srcImgInfo === false) {
            return;
        }

        $imgTypes = [
            IMAGETYPE_GIF => IMG_GIF,
            IMAGETYPE_JPEG => IMG_JPEG,
            IMAGETYPE_PNG => IMG_PNG,
            IMAGETYPE_BMP => IMG_WBMP,
            IMAGETYPE_WBMP => IMG_WBMP
        ];
        switch ($imgTypes[$srcImgInfo[2]]) {
            case IMG_JPEG:
                shell_exec(strtr($this->jpegOptimizer, [
                    '{from}' => escapeshellarg($src),
                    '{to}' => escapeshellarg($src)
                ]));
                break;

            case IMG_PNG:
                shell_exec(strtr($this->pngOptimizer, [
                    '{from}' => escapeshellarg($src),
                    '{to}' => escapeshellarg($src)
                ]));
                break;

            default:
                return;
        }
    }
}
