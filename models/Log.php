<?php

namespace Modules\Twitterauth\Models;

use Ilch\Date;
use Ilch\Model;
use DateTimeZone;
use Ilch\Registry;

class Log extends Model
{
    /**
     * The log id
     *
     * @var int
     */
    protected $id;

    /**
     * The log type
     *
     * @var string
     */
    protected $type;

    /**
     * The log message as json
     *
     * @var string
     */
    protected $message;

    /**
     * Additional data to debug the message
     *
     * @var string
     */
    protected $data;

    /**
     * The creation date
     *
     * @var string
     */
    protected $created_at;

    public function __construct($params = null) {}

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param string $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function hasData()
    {
        return !empty($this->getData());
    }

    /**
     * @return Date
     */
    public function getLocalizedCreatedAt()
    {
        $config = Registry::get('config');
        $timezone = new DateTimeZone($config->get('timezone'));

        return (new Date($this->getCreatedAt()))->setTimezone($timezone);
    }
}
