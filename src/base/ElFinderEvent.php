<?php
/**
 * Created by PhpStorm.
 * User: karlen
 * Date: 15.09.2017
 * Time: 14:31
 */

namespace simialbi\yii2\elfinder\base;


use yii\base\Event;

class ElFinderEvent extends Event
{
    /**
     * @var array arguments array
     */
    public array $arguments;
    /**
     * @var array result array
     */
    public array $result;
    /**
     * @var string relative path from the upload target
     */
    public string $path;
    /**
     * @var string file name
     */
    public string $fileName;
    /**
     * @var string file tmp name
     */
    public string $fileTmpName;
    /**
     * @var \elFinderVolumeDriver $volume Volume Driver Instance
     */
    public \elFinderVolumeDriver $volume;
}
