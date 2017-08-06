<?php
// COPYRIGHT (c) 2016 Tobias Schwarz
//
// MIT License
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

/**
 * @copyright Tobias Schwarz
 * @author Tobias Schwarz <code@tobias-schwarz.me>
 * @license MIT
 */
namespace Modules\Twitterauth\Mappers;

use Ilch\Date;
use Ilch\Mapper;
use Modules\Twitterauth\Models\Log;

class DbLog extends Mapper {
    /**
     * Shortcut for an info log message
     *
     * @param $message  string  The message
     * @param $data     mixed   Additional information
     *
     * @return int
     */
    public function info($message, $data = [])
    {
        return $this->log('info', $message, $data);
    }

    /**
     * Shortcut for an debug log message
     *
     * @param $message  string  The message
     * @param $data     mixed   Additional information
     *
     * @return int
     */
    public function debug($message, $data = [])
    {
        return $this->log('debug', $message, $data);
    }

    /**
     * Shortcut for an error log message
     *
     * @param $message  string  The message
     * @param $data     mixed   Additional information
     *
     * @return int
     */
    public function error($message, $data = [])
    {
        return $this->log('error', $message, $data);
    }

    /**
     * Inserts a log message into the database
     *
     * @param $type     string  Log type (e.g. error, info, debug)
     * @param $message  string  The log message
     * @param $data     mixed   Additional information regarding the log message
     *
     * @return int
     */
    public function log($type, $message, $data)
    {
        if (! $this->isValidJson($data)) {
            $data = json_encode($data);
        }

        return $this->db()
            ->insert('twitterauth_log')
            ->values([
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'created_at' => (new Date())->toDb()
            ])
            ->execute();
    }

    /**
     * Get log messages
     *
     * @return \Ilch\Database\Mysql\Result
     */
    public function getAll()
    {
        $result = $this->db()
            ->select('*')
            ->from('twitterauth_log')
            ->order(['created_at' => 'DESC'])
            ->limit(50)
            ->execute();

        return $result;
    }

    /**
     * Finds the log message with the given id
     *
     * @param $logId
     * @param string $fields
     *
     * @return Log|null
     */
    public function find($logId, $fields = '*')
    {
        return $this->db()
            ->select($fields)
            ->from('twitterauth_log')
            ->where(['id' => $logId])
            ->limit(1)
            ->execute()
            ->fetchObject(Log::class, []);
    }

    /**
     * Clears the log
     *
     * @return int  Affected rows
     */
    public function clear()
    {
        return $this->db()
            ->delete('twitterauth_log')
            ->execute();
    }

    /**
     * Deletes the given log message
     *
     * @param $logId
     *
     * @return \Ilch\Database\Mysql\Result|int
     *
     * @throws \Exception
     */
    public function delete($logId)
    {
        $log = $this->find($logId);

        if (is_null($log)) {
            throw new \Exception('No log with id '. $logId . ' found.');
        }

        return $this->db()
            ->delete('twitterauth_log')
            ->where(['id' => $log->getId()])
            ->execute();
    }

    /**
     * Checks if the value is valid json
     *
     * @param $value
     *
     * @return bool
     */
    protected function isValidJson($value)
    {
        $temp = @json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && ! is_null($temp);
    }
}
