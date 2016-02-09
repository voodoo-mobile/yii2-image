<?php
namespace vm\image;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image;

/**
 * Class Thumbnailer
 * @package vm\image
 *
 *          Generates thumbnails for the selected file identified by its path.
 *          Use:
 *              (new Thumbnailer([
 *                  'imagePath' => 'uploads/post/12-image.jpg'
 *              ]))->generate([120, 120])
 */
class Thumbnailer extends Component
{
    /**
     * @var null The full or relative path of the image
     */
    public $imagePath = null;

    /**
     * Generates a thumbnail of the image
     *
     * @param $dimension
     *
     * @return string
     */
    public function generate($dimension)
    {
        list($width, $height) = Utils::getDimension($dimension);
        list($path, $basename, $extension) = $this->pathComponents();

        $thumbnail = sprintf('%s/%s-%dx%d.%s', $path, $basename, $width, $height, $extension);
        Image::thumbnail($this->imagePath, $width, $height)->save($thumbnail);

        return $thumbnail;
    }

    /**
     * Deletes all of thumbnails of the image
     *
     * @return bool
     */
    public function clear()
    {
        list($path, $basename) = $this->pathComponents();

        if (file_exists($path)) {
            $files = FileHelper::findFiles($path, ['only' => [$basename . '-*']]);

            foreach ($files as $file) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Cracks the image path
     *
     * @return array
     */
    private function pathComponents()
    {
        $info = pathinfo($this->imagePath);

        /** @noinspection SpellCheckingInspection */
        $path     = ArrayHelper::getValue($info, 'dirname');
        $basename = ArrayHelper::getValue($info, 'filename');
        $ext      = ArrayHelper::getValue($info, 'extension');

        return [$path, $basename, $ext];
    }
}