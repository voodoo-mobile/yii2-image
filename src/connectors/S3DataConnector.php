<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 07/01/2017
 * Time: 02:43
 */

namespace vr\image\connectors;

use Aws\Credentials\Credentials;
use Aws\Result;
use Aws\S3\S3Client;
use vr\image\Mediator;

/**
 * Class S3DataConnector
 * @package vr\image\connectors
 */
class S3DataConnector extends DataConnector
{
    /**
     * @var
     */
    public $accessKeyId;

    /**
     * @var
     */
    public $secretAccessKey;

    /**
     * @var string
     */
    public $region = 'eu-west-1';

    /**
     * @var
     */
    public $bucket;

    /** @var S3Client */
    private $client;

    /**
     *
     */
    public function init()
    {
        parent::init();

        $this->client = new S3Client([
            'credentials' => new Credentials($this->accessKeyId, $this->secretAccessKey),
            'region'      => $this->region,
            'version'     => 'latest',
        ]);
    }

    /**
     * @param Mediator $mediator
     * @param          $filename
     *
     * @return mixed
     */
    public function upload($mediator, $filename)
    {
        $this->createBucket();

        $this->client->upload($this->bucket, $this->locate($filename),
            file_get_contents($mediator->filename), 'public-read');

        return true;
    }

    /**
     * @param $filename
     *
     * @return mixed
     */
    public function locate($filename)
    {
        return $this->folder . '/' . $filename;
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     */
    public function rename($source, $destination)
    {
        if ($source === $destination) {
            return true;
        }

        $destination = $this->locate($destination);

        $this->client->copyObject([
            'Bucket'     => $this->bucket,
            'Key'        => $destination,
            'CopySource' => "{$this->bucket}/{$this->locate($source)}",
            'ACL'        => 'public-read',
        ]);

        $this->client->waitUntil('ObjectExists', [
            'Bucket' => $this->bucket,
            'Key'    => $destination,
        ]);

        return $this->drop($source);
    }

    /**
     * @param $filename
     *
     * @return mixed
     */
    public function drop($filename)
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $this->locate($filename),
        ]);

        return $this->cleanUp($filename);
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function cleanUp($filename)
    {
        $mask = $this->locate(pathinfo($filename, PATHINFO_FILENAME));

        /** @var Result $result */
        $result = $this->client->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $mask,
        ]);

        $objects = $result['Contents'];

        if (!is_array($objects)) {
            return true;
        }

        foreach ($objects as $object) {
            $key    = $object['Key'];
            $suffix = substr($key, strlen($mask));

            if (count(explode('x', $suffix)) > 1) {
                $this->client->deleteObject([
                    'Bucket' => $this->bucket,
                    'Key'    => $key,
                ]);
            }
        }

        return true;
    }

    /**
     * @param $filename
     *
     * @return mixed
     */
    public function exists($filename)
    {
        return $this->client->doesObjectExist($this->bucket, $this->locate($filename));
    }

    /**
     * @param      $filename
     * @param bool $utm
     *
     * @return mixed
     */
    public function url($filename, $utm = false)
    {
        if (!$filename) {
            return null;
        }

        $url = "https://s3-{$this->region}.amazonaws.com/{$this->bucket}/{$this->locate($filename)}";

        if ($utm) {
            $url .= '?utm=' . uniqid();
        }

        return $url;
    }

    /**
     *
     */
    private function createBucket()
    {
        if (!$this->client->doesBucketExist($this->bucket)) {
            $this->client->createBucket([
                'Bucket' => $this->bucket,
            ]);

            $this->client->waitUntil('BucketExists', ['Bucket' => $this->bucket]);
        }
    }
}